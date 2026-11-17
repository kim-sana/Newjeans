<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\AlbumRepository;
use App\Services\CheckoutService;
use App\Shipping\DestinationCatalog;
use App\Shipping\ShippingCalculator;
use App\Support\Cart;
use RuntimeException;

final class CheckoutController
{
    use PublicViewTrait;

    private const NONCE_KEY = 'checkout_nonce';

    private const LAST_ORDER_KEY = 'last_order_token';

    private const MAX_ITEM_QUANTITY = 99;

    private AlbumRepository $albums;

    private DestinationCatalog $catalog;

    private ShippingCalculator $calculator;

    private CheckoutService $service;

    public function __construct(
        ?AlbumRepository $albums = null,
        ?DestinationCatalog $catalog = null,
        ?ShippingCalculator $calculator = null,
        ?CheckoutService $service = null
    ) {
        $this->albums = $albums ?? new AlbumRepository();
        $this->catalog = $catalog ?? new DestinationCatalog();
        $this->calculator = $calculator ?? new ShippingCalculator();
        $this->service = $service ?? new CheckoutService();
    }

    public function show(Request $request, array $params = []): Response
    {
        $cart = Cart::items();

        if ($cart === []) {
            flash('error', 'Your cart is empty. Add an album before checking out.');

            return Response::redirect(url('/cart'), 303);
        }

        $summary = $this->summarize($cart);

        if ($summary['hasIssues']) {
            flash('error', 'Some items in your cart are unavailable. Review your cart before checking out.');

            return Response::redirect(url('/cart'), 303);
        }

        $shippingConfigured = $this->calculator->isConfigured();
        $countries = [];
        $cities = [];
        $quote = null;
        $destinationError = false;
        $selectedCountry = strtoupper($this->scalar(old('country_code', '')));
        $oldCityId = old('city_id', 0);
        $selectedCityId = is_scalar($oldCityId) ? (int) $oldCityId : 0;

        try {
            $countries = $this->catalog->countries();

            if ($selectedCountry !== '' && $this->catalog->isCountry($selectedCountry)) {
                $cities = $this->catalog->cities($selectedCountry);
            } else {
                $selectedCountry = '';
            }

            if ($selectedCountry !== '' && $selectedCityId > 0) {
                $city = $this->catalog->findCity($selectedCountry, $selectedCityId);

                if ($city === null) {
                    $selectedCityId = 0;
                } elseif ($shippingConfigured) {
                    $quote = $this->calculator->quote($city['lat'], $city['lng']);
                }
            }
        } catch (RuntimeException) {
            $destinationError = true;
            $countries = [];
            $cities = [];
            $selectedCountry = '';
            $selectedCityId = 0;
            $quote = null;
        }

        $subtotal = (float) $summary['subtotal'];
        $shippingAmount = is_array($quote) ? (float) $quote['shipping_usd'] : null;

        return $this->renderPage('pages/checkout', [
            'items' => $summary['items'],
            'subtotal' => $subtotal,
            'countries' => $countries,
            'cities' => $cities,
            'selectedCountry' => $selectedCountry,
            'selectedCityId' => $selectedCityId,
            'shippingConfigured' => $shippingConfigured,
            'destinationError' => $destinationError,
            'quote' => $quote,
            'estimatedTotal' => $shippingAmount === null ? $subtotal : round($subtotal + $shippingAmount, 2),
            'nonce' => $this->issueNonce(),
            'errors' => form_errors(),
        ], [
            'title' => 'Checkout — NewJeans',
            'description' => 'Complete your NewJeans album order as a guest.',
        ], 'cart', ['purchase.css']);
    }

    public function store(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $nonceResponse = $this->consumeNonce($request);

        if ($nonceResponse instanceof Response) {
            return $nonceResponse;
        }

        $cart = Cart::items();

        if ($cart === []) {
            flash('error', 'Your cart is empty. Add an album before checking out.');

            return Response::redirect(url('/cart'), 303);
        }

        if (!$this->calculator->isConfigured()) {
            flash('error', 'Checkout is unavailable because shipping estimates are not configured.');

            return Response::redirect(url('/checkout'), 303);
        }

        $input = $this->checkoutInput($request);

        try {
            $errors = $this->validateInput($input);
        } catch (RuntimeException) {
            flash('error', 'Shipping destination data is unavailable right now.');

            return Response::redirect(url('/checkout'), 303);
        }

        if ($errors !== []) {
            return $this->backWithErrors($input, $errors);
        }

        $summary = $this->summarize($cart);

        if ($summary['hasIssues']) {
            flash('error', 'Some items in your cart are no longer available. Review your cart and try again.');

            return Response::redirect(url('/cart'), 303);
        }

        try {
            $resolved = $this->resolveDestination($input);
        } catch (RuntimeException) {
            return $this->backWithErrors($input, [
                'city_id' => 'Shipping estimates are unavailable for the selected destination.',
            ]);
        }

        try {
            $order = $this->service->createOrder(
                $cart,
                $this->recipient($input),
                $resolved['destination'],
                $resolved['quote']
            );
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());

            return Response::redirect(url('/cart'), 303);
        }

        $token = (string) ($order['access_token'] ?? '');

        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            flash('error', 'We could not open the payment page. Your cart is unchanged.');

            return Response::redirect(url('/cart'), 303);
        }

        Cart::clear();
        $_SESSION[self::LAST_ORDER_KEY] = $token;
        clear_form_state();

        return Response::redirect(url('/checkout/' . $token . '/payment'), 303);
    }

    /**
     * @param array<int, int> $cart
     * @return array{items: list<array<string, mixed>>, subtotal: float, hasIssues: bool}
     */
    private function summarize(array $cart): array
    {
        $rows = $this->albums->findMany(array_keys($cart));
        $items = [];
        $subtotal = 0.0;
        $hasIssues = false;

        foreach ($cart as $albumId => $quantity) {
            $album = $rows[$albumId] ?? null;
            $isArray = is_array($album);
            $name = $isArray ? (string) ($album['name'] ?? 'Album') : 'Unavailable album';
            $cover = $isArray ? (string) ($album['cover_album'] ?? '') : '';
            $price = $isArray && is_numeric($album['price'] ?? null) ? (float) $album['price'] : 0.0;
            $stock = $isArray ? max(0, (int) ($album['stock'] ?? 0)) : 0;
            $isForSale = $isArray && (int) ($album['is_for_sale'] ?? 0) === 1;
            $lineTotal = round($price * $quantity, 2);
            $issue = null;

            if (!$isArray) {
                $issue = 'This album is no longer available.';
            } elseif (!$isForSale) {
                $issue = 'This album is no longer for sale.';
            } elseif ($stock <= 0) {
                $issue = 'This album is out of stock.';
            } elseif ($quantity > min($stock, self::MAX_ITEM_QUANTITY)) {
                $issue = sprintf('Only %d left in stock.', $stock);
            }

            if ($isForSale) {
                $subtotal += $lineTotal;
            }

            if ($issue !== null) {
                $hasIssues = true;
            }

            $items[] = [
                'id' => $albumId,
                'name' => $name,
                'cover' => $cover,
                'price' => $price,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'issue' => $issue,
            ];
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'hasIssues' => $hasIssues,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function checkoutInput(Request $request): array
    {
        return [
            'recipient_name' => $this->scalar($request->post('recipient_name')),
            'recipient_phone' => $this->scalar($request->post('recipient_phone')),
            'shipping_address' => $this->scalar($request->post('shipping_address')),
            'country_code' => strtoupper($this->scalar($request->post('country_code'))),
            'city_id' => $this->scalar($request->post('city_id')),
        ];
    }

    /**
     * @param array<string, string> $input
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        $errors = Validator::make($input, [
            'recipient_name' => 'required|max:120',
            'recipient_phone' => 'required|min:7|max:30',
            'shipping_address' => 'required|max:255',
            'country_code' => 'required',
            'city_id' => 'required|integer',
        ]);

        if ($input['country_code'] === '') {
            $errors['country_code'] = 'Choose a destination country.';
        }

        if ($input['city_id'] === '') {
            $errors['city_id'] = 'Choose a destination city.';
        }

        if (
            !isset($errors['recipient_phone'])
            && $input['recipient_phone'] !== ''
            && preg_match('/^[0-9+()\- ]+$/', $input['recipient_phone']) !== 1
        ) {
            $errors['recipient_phone'] = 'Recipient phone may only contain digits, spaces, +, -, ( and ).';
        }

        if (!isset($errors['country_code']) && !$this->catalog->isCountry($input['country_code'])) {
            $errors['country_code'] = 'Choose a supported destination country.';
        }

        if (!isset($errors['city_id']) && !isset($errors['country_code'])) {
            if ($this->catalog->findCity($input['country_code'], (int) $input['city_id']) === null) {
                $errors['city_id'] = 'Choose a city in the selected country.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, string> $input
     * @return array{
     *     destination: array{country_code: string, country: string, city_id: int, city: string,
     *         region: ?string, latitude: float, longitude: float},
     *     quote: array{distance_km: float, shipping_usd: float, currency: string}
     * }
     */
    private function resolveDestination(array $input): array
    {
        $code = $input['country_code'];
        $country = $this->countryName($code);
        $city = $this->catalog->findCity($code, (int) $input['city_id']);

        if ($country === null || $city === null) {
            throw new RuntimeException('Choose a supported destination country and city.');
        }

        $quote = $this->calculator->quote($city['lat'], $city['lng']);

        if ($quote === null) {
            throw new RuntimeException('Shipping estimates are unavailable for the selected destination.');
        }

        return [
            'destination' => [
                'country_code' => $code,
                'country' => $country,
                'city_id' => $city['id'],
                'city' => $city['name'],
                'region' => $city['region'],
                'latitude' => $city['lat'],
                'longitude' => $city['lng'],
            ],
            'quote' => $quote,
        ];
    }

    /**
     * @param array<string, string> $input
     * @return array{recipient_name: string, recipient_phone: string, shipping_address: string}
     */
    private function recipient(array $input): array
    {
        return [
            'recipient_name' => $input['recipient_name'],
            'recipient_phone' => $input['recipient_phone'],
            'shipping_address' => $input['shipping_address'],
        ];
    }

    private function countryName(string $code): ?string
    {
        foreach ($this->catalog->countries() as $country) {
            if ($country['code'] === $code) {
                return $country['name'];
            }
        }

        return null;
    }

    /**
     * Stores old input and errors, prepares a fresh nonce, and redirects back to checkout.
     *
     * @param array<string, string> $input
     * @param array<string, string> $errors
     */
    private function backWithErrors(array $input, array $errors): Response
    {
        set_old($input);
        set_form_errors($errors);
        $this->issueNonce();

        return Response::redirect(url('/checkout'), 303);
    }

    private function issueNonce(): string
    {
        $nonce = bin2hex(random_bytes(16));
        $_SESSION[self::NONCE_KEY] = $nonce;

        return $nonce;
    }

    /**
     * Consumes the one-time checkout nonce. A response is returned when the request must stop.
     */
    private function consumeNonce(Request $request): ?Response
    {
        $posted = $request->post('checkout_nonce');
        $stored = $_SESSION[self::NONCE_KEY] ?? null;

        if (
            is_string($posted)
            && is_string($stored)
            && $posted !== ''
            && hash_equals($stored, $posted)
        ) {
            unset($_SESSION[self::NONCE_KEY]);

            return null;
        }

        $token = $_SESSION[self::LAST_ORDER_KEY] ?? null;

        if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1) {
            return Response::redirect(url('/checkout/' . $token . '/payment'), 303);
        }

        return Response::html(
            'This checkout form has expired. Return to your cart and try again.',
            419
        );
    }

    private function csrfValid(Request $request): bool
    {
        $token = $request->post('_token');

        return is_string($token) && Csrf::validate($token);
    }

    private function csrfFailure(): Response
    {
        return Response::html('Invalid or expired security token.', 419);
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
