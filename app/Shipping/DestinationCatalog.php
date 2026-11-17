<?php

declare(strict_types=1);

namespace App\Shipping;

use JsonException;
use RuntimeException;

final class DestinationCatalog
{
    private string $datasetPath;

    /** @var array<string, list<array{code: string, name: string}>> */
    private static array $countryCache = [];

    /** @var array<string, list<array{id: int, name: string, region: ?string, lat: float, lng: float, population: int}>> */
    private static array $cityCache = [];

    public function __construct(?string $datasetPath = null)
    {
        $path = $datasetPath ?? (string) config('shipping.dataset_path', base_path('database/data/shipping'));

        $this->datasetPath = rtrim($path, '/\\');
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function countries(): array
    {
        if (!isset(self::$countryCache[$this->datasetPath])) {
            self::$countryCache[$this->datasetPath] = $this->loadCountries();
        }

        return self::$countryCache[$this->datasetPath];
    }

    /**
     * @return list<array{id: int, name: string, region: ?string, lat: float, lng: float, population: int}>
     */
    public function cities(string $countryCode): array
    {
        $code = $this->normalizeCode($countryCode);

        if ($code === null || !$this->isCountry($code)) {
            return [];
        }

        $key = $this->datasetPath . '|' . $code;

        if (!isset(self::$cityCache[$key])) {
            self::$cityCache[$key] = $this->loadCities($code);
        }

        return self::$cityCache[$key];
    }

    public function isCountry(string $countryCode): bool
    {
        $code = $this->normalizeCode($countryCode);

        if ($code === null) {
            return false;
        }

        foreach ($this->countries() as $country) {
            if ($country['code'] === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{id: int, name: string, region: ?string, lat: float, lng: float, population: int}|null
     */
    public function findCity(string $countryCode, int $cityId): ?array
    {
        if ($cityId <= 0) {
            return null;
        }

        foreach ($this->cities($countryCode) as $city) {
            if ($city['id'] === $cityId) {
                return $city;
            }
        }

        return null;
    }

    private function normalizeCode(string $countryCode): ?string
    {
        $code = strtoupper(trim($countryCode));

        return preg_match('/^[A-Z]{2}$/', $code) === 1 ? $code : null;
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private function loadCountries(): array
    {
        $rows = $this->decode($this->datasetPath . '/countries.json');
        $countries = [];
        $seen = [];

        foreach ($rows as $row) {
            if (
                !is_array($row)
                || !isset($row['code'], $row['name'])
                || !is_string($row['code'])
                || !is_string($row['name'])
            ) {
                throw new RuntimeException('Shipping dataset is malformed: countries.json.');
            }

            $code = $this->normalizeCode($row['code']);

            if ($code === null) {
                throw new RuntimeException('Shipping dataset is malformed: countries.json has an invalid country code.');
            }

            if (isset($seen[$code])) {
                throw new RuntimeException('Shipping dataset is malformed: duplicate country code ' . $code . '.');
            }

            $seen[$code] = true;
            $countries[] = ['code' => $code, 'name' => $row['name']];
        }

        if ($countries === []) {
            throw new RuntimeException('Shipping dataset is empty: countries.json.');
        }

        return $countries;
    }

    /**
     * @return list<array{id: int, name: string, region: ?string, lat: float, lng: float, population: int}>
     */
    private function loadCities(string $countryCode): array
    {
        $rows = $this->decode($this->datasetPath . '/cities/' . $countryCode . '.json');
        $cities = [];

        foreach ($rows as $row) {
            if (
                !is_array($row)
                || !isset($row['id'], $row['name'], $row['lat'], $row['lng'], $row['population'])
                || !is_string($row['name'])
                || !is_numeric($row['id'])
                || !is_numeric($row['lat'])
                || !is_numeric($row['lng'])
                || !is_numeric($row['population'])
            ) {
                throw new RuntimeException(
                    'Shipping dataset is malformed: cities/' . $countryCode . '.json.'
                );
            }

            $region = $row['region'] ?? null;

            if ($region !== null && !is_string($region)) {
                throw new RuntimeException(
                    'Shipping dataset is malformed: cities/' . $countryCode . '.json has an invalid region.'
                );
            }

            $cities[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'region' => $region,
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lng'],
                'population' => (int) $row['population'],
            ];
        }

        if ($cities === []) {
            throw new RuntimeException('Shipping dataset is empty: cities/' . $countryCode . '.json.');
        }

        return $cities;
    }

    /**
     * @return list<mixed>
     */
    private function decode(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Shipping dataset file not found: ' . $path . '.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Shipping dataset file could not be read: ' . $path . '.');
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Shipping dataset file is not valid JSON: ' . $path . '.',
                0,
                $exception
            );
        }

        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException('Shipping dataset file has an invalid structure: ' . $path . '.');
        }

        return $data;
    }
}
