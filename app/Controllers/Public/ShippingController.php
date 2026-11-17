<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Shipping\DestinationCatalog;
use App\Shipping\ShippingCalculator;
use RuntimeException;

final class ShippingController
{
    private DestinationCatalog $catalog;

    private ShippingCalculator $calculator;

    public function __construct(?DestinationCatalog $catalog = null, ?ShippingCalculator $calculator = null)
    {
        $this->catalog = $catalog ?? new DestinationCatalog();
        $this->calculator = $calculator ?? new ShippingCalculator();
    }

    public function cities(Request $request, array $params = []): Response
    {
        $country = $this->queryString($request, 'country');

        if ($country === null || !$this->catalog->isCountry($country)) {
            return $this->error('Choose a supported destination country.', 422);
        }

        try {
            $rows = $this->catalog->cities($country);
        } catch (RuntimeException) {
            return $this->error('Shipping destination data is unavailable.', 500);
        }

        $cities = [];

        foreach ($rows as $city) {
            $cities[] = [
                'id' => $city['id'],
                'name' => $city['name'],
                'region' => $city['region'],
            ];
        }

        return $this->noStore(Response::json([
            'country' => strtoupper(trim($country)),
            'cities' => $cities,
        ]));
    }

    public function estimate(Request $request, array $params = []): Response
    {
        if (!$this->calculator->isConfigured()) {
            return $this->error('Shipping estimates are not configured. Checkout is disabled.', 503);
        }

        $country = $this->queryString($request, 'country');
        $cityId = $this->queryPositiveInt($request, 'city');

        if ($country === null || $cityId === null || !$this->catalog->isCountry($country)) {
            return $this->error('Choose a supported destination country and city.', 422);
        }

        try {
            $city = $this->catalog->findCity($country, $cityId);
        } catch (RuntimeException) {
            return $this->error('Shipping destination data is unavailable.', 500);
        }

        if ($city === null) {
            return $this->error('That city is not available for the selected country.', 422);
        }

        $quote = $this->calculator->quote($city['lat'], $city['lng']);

        if ($quote === null) {
            return $this->error('Shipping estimates are not configured. Checkout is disabled.', 503);
        }

        return $this->noStore(Response::json([
            'distance_km' => $quote['distance_km'],
            'shipping_usd' => $quote['shipping_usd'],
            'currency' => $quote['currency'],
        ]));
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function queryPositiveInt(Request $request, string $key): ?int
    {
        $value = $request->query($key);

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^[1-9][0-9]{0,9}$/', $value) !== 1) {
            return null;
        }

        return (int) $value;
    }

    private function error(string $message, int $status): Response
    {
        return $this->noStore(Response::json(['error' => $message], $status));
    }

    private function noStore(Response $response): Response
    {
        return $response->withHeader('Cache-Control', 'no-store');
    }
}
