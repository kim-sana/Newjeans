<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Models\AlbumRepository;
use App\Support\Cart;

final class CartController
{
    use PublicViewTrait;

    private AlbumRepository $albums;

    public function __construct(?AlbumRepository $albums = null)
    {
        $this->albums = $albums ?? new AlbumRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        $cart = Cart::items();
        $albumRows = $this->albums->findMany(array_keys($cart));
        $items = [];
        $subtotal = 0.0;
        $hasIssues = false;

        foreach ($cart as $albumId => $quantity) {
            $album = $albumRows[$albumId] ?? null;
            $isArray = is_array($album);
            $name = $isArray ? (string) ($album['name'] ?? 'Album') : 'Unavailable album';
            $cover = $isArray ? (string) ($album['cover_album'] ?? '') : '';
            $price = $isArray && is_numeric($album['price'] ?? null) ? (float) $album['price'] : 0.0;
            $stock = $isArray ? max(0, (int) ($album['stock'] ?? 0)) : 0;
            $isForSale = $isArray && (int) ($album['is_for_sale'] ?? 0) === 1;
            $lineTotal = round($price * $quantity, 2);
            $issue = null;

            if (!$isArray) {
                $issue = 'This album is no longer available. Remove it to continue.';
            } elseif (!$isForSale) {
                $issue = 'This album is no longer for sale. Remove it to continue.';
            } elseif ($stock <= 0) {
                $issue = 'This album is out of stock. Remove it to continue.';
            } elseif ($quantity > $stock) {
                $issue = sprintf(
                    'Only %d left in stock. Reduce the quantity to continue.',
                    $stock
                );
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
                'stock' => $stock,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'max_quantity' => max(1, min($stock, 99)),
                'issue' => $issue,
            ];
        }

        return $this->renderPage('pages/cart', [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'hasIssues' => $hasIssues,
        ], [
            'title' => 'Your Cart — NewJeans',
            'description' => 'Review your NewJeans album selections before checkout.',
        ], 'cart', ['purchase.css']);
    }

    public function add(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $albumId = $this->positiveInt($request->post('album_id'));
        $quantity = $this->positiveInt($request->post('quantity', 1));

        if ($albumId === null || $quantity === null || $quantity > 99) {
            flash('error', 'Invalid album or quantity.');

            return Response::redirect(url('/cart'), 303);
        }

        $album = $this->albums->find($albumId);

        if ($album === null) {
            flash('error', 'That album is no longer available.');

            return Response::redirect(url('/cart'), 303);
        }

        $name = (string) ($album['name'] ?? 'This album');

        if ((int) ($album['is_for_sale'] ?? 0) !== 1) {
            flash('error', $name . ' is not for sale.');

            return Response::redirect(url('/cart'), 303);
        }

        $stock = max(0, (int) ($album['stock'] ?? 0));

        if ($stock <= 0) {
            flash('error', $name . ' is out of stock.');

            return Response::redirect(url('/cart'), 303);
        }

        $current = Cart::items()[$albumId] ?? 0;
        $remaining = min($stock, 99) - $current;

        if ($remaining <= 0) {
            flash('error', 'Your cart already has the maximum available quantity for ' . $name . '.');

            return Response::redirect(url('/cart'), 303);
        }

        Cart::add($albumId, min($quantity, $remaining));
        flash('success', $name . ' was added to your cart.');

        return Response::redirect(url('/cart'), 303);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $albumId = $this->positiveInt($params['id'] ?? null);
        $quantity = $this->positiveInt($request->post('quantity'));

        if ($albumId === null) {
            flash('error', 'Invalid album.');

            return Response::redirect(url('/cart'), 303);
        }

        if ($quantity === null || $quantity > 99) {
            flash('error', 'Quantity must be between 1 and 99.');

            return Response::redirect(url('/cart'), 303);
        }

        $album = $this->albums->find($albumId);

        if ($album === null) {
            flash('error', 'That album is no longer available.');

            return Response::redirect(url('/cart'), 303);
        }

        $name = (string) ($album['name'] ?? 'This album');

        if ((int) ($album['is_for_sale'] ?? 0) !== 1) {
            flash('error', $name . ' is not for sale.');

            return Response::redirect(url('/cart'), 303);
        }

        $stock = max(0, (int) ($album['stock'] ?? 0));

        if ($stock <= 0) {
            flash('error', $name . ' is out of stock.');

            return Response::redirect(url('/cart'), 303);
        }

        $max = min($stock, 99);

        if ($quantity > $max) {
            flash('error', sprintf('Only %d of %s available.', $max, $name));

            return Response::redirect(url('/cart'), 303);
        }

        Cart::setQuantity($albumId, $quantity);
        flash('success', 'Your cart was updated.');

        return Response::redirect(url('/cart'), 303);
    }

    public function remove(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $albumId = $this->positiveInt($params['id'] ?? null);

        if ($albumId === null) {
            flash('error', 'Invalid album.');

            return Response::redirect(url('/cart'), 303);
        }

        Cart::remove($albumId);
        flash('success', 'Item removed from your cart.');

        return Response::redirect(url('/cart'), 303);
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

    private function positiveInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1) {
            $number = (int) $value;

            return $number > 0 ? $number : null;
        }

        return null;
    }
}
