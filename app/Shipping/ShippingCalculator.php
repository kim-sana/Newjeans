<?php

declare(strict_types=1);

namespace App\Shipping;

final class ShippingCalculator
{
    private const EARTH_RADIUS_KM = 6371.0088;

    private ?float $baseFeeUsd;

    private ?float $feePerKmUsd;

    /** @var array{name: string, latitude: float, longitude: float} */
    private array $origin;

    /**
     * @param array{base_fee_usd?: mixed, fee_per_km_usd?: mixed}|null $rates
     * @param array{name?: mixed, latitude?: mixed, longitude?: mixed}|null $origin
     */
    public function __construct(?array $rates = null, ?array $origin = null)
    {
        $rates ??= (array) config('shipping.rates', []);
        $origin ??= (array) config('shipping.origin', []);

        $this->baseFeeUsd = $this->rate($rates['base_fee_usd'] ?? null);
        $this->feePerKmUsd = $this->rate($rates['fee_per_km_usd'] ?? null);
        $this->origin = [
            'name' => (string) ($origin['name'] ?? 'Seoul, South Korea'),
            'latitude' => (float) ($origin['latitude'] ?? 37.5665),
            'longitude' => (float) ($origin['longitude'] ?? 126.9780),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->baseFeeUsd !== null && $this->feePerKmUsd !== null;
    }

    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $a = sin($deltaPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;
        $a = max(0.0, min(1.0, $a));

        return 2 * self::EARTH_RADIUS_KM * asin(sqrt($a));
    }

    /**
     * @return array{distance_km: float, shipping_usd: float, currency: string}|null
     */
    public function quote(float $latitude, float $longitude): ?array
    {
        if (!$this->isConfigured() || !$this->validCoordinates($latitude, $longitude)) {
            return null;
        }

        $distance = $this->distanceKm(
            $this->origin['latitude'],
            $this->origin['longitude'],
            $latitude,
            $longitude
        );

        $shipping = (float) $this->baseFeeUsd + $distance * (float) $this->feePerKmUsd;

        return [
            'distance_km' => round($distance, 2),
            'shipping_usd' => round($shipping, 2),
            'currency' => (string) config('shipping.currency', 'USD'),
        ];
    }

    /**
     * @return array{name: string, latitude: float, longitude: float}
     */
    public function origin(): array
    {
        return $this->origin;
    }

    private function rate(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $rate = (float) $value;

        return $rate >= 0.0 ? $rate : null;
    }

    private function validCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= -90.0 && $latitude <= 90.0
            && $longitude >= -180.0 && $longitude <= 180.0;
    }
}
