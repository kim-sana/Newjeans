<?php

declare(strict_types=1);

use App\Shipping\DestinationCatalog;
use App\Shipping\ShippingCalculator;

require dirname(__DIR__) . '/vendor/autoload.php';

$checks = 0;
$failures = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $checks, $failures;

    $checks++;

    if ($condition) {
        echo 'PASS  ' . $label . ($detail === '' ? '' : ' (' . $detail . ')') . PHP_EOL;

        return;
    }

    $failures++;
    echo 'FAIL  ' . $label . ($detail === '' ? '' : ' (' . $detail . ')') . PHP_EOL;
}

function checkClose(string $label, float $actual, float $expected, float $tolerance): void
{
    check(
        $label,
        abs($actual - $expected) <= $tolerance,
        sprintf('actual %.2f, expected %.2f +/- %.2f', $actual, $expected, $tolerance)
    );
}

function expectRuntimeException(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (RuntimeException $exception) {
        check($label, true, $exception->getMessage());

        return;
    }

    check($label, false, 'no RuntimeException thrown');
}

$root = dirname(__DIR__);
$version = json_decode(
    (string) file_get_contents($root . '/database/data/shipping/version.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$catalog = new DestinationCatalog();
$countries = $catalog->countries();

echo 'Catalog' . PHP_EOL;

check('countries.json loads', $countries !== [], count($countries) . ' countries');
check(
    'country count matches version.json',
    count($countries) === (int) $version['country_count'],
    count($countries) . ' vs ' . $version['country_count']
);

$sorted = true;

for ($i = 1; $i < count($countries); $i++) {
    if (strcasecmp($countries[$i - 1]['name'], $countries[$i]['name']) > 0) {
        $sorted = false;
        break;
    }
}

check('countries are sorted by name', $sorted);
check('isCountry KR', $catalog->isCountry('KR'));
check('isCountry normalizes lowercase', $catalog->isCountry('kr'));
check('isCountry ZZ is false', !$catalog->isCountry('ZZ'));
check('isCountry rejects traversal input', !$catalog->isCountry('../US'));

$korea = $catalog->cities('KR');

check('KR cities load', $korea !== [], count($korea) . ' cities');
check('KR first city is Seoul', $korea[0]['name'] === 'Seoul' && $korea[0]['id'] === 1835848);
check('cities normalizes lowercase code', count($catalog->cities('kr')) === count($korea));
check('unknown country has no cities', $catalog->cities('ZZ') === []);
check('traversal country has no cities', $catalog->cities('../cities/US') === []);
check('every KR city has a valid shape', (static function (array $cities): bool {
    foreach ($cities as $city) {
        if (
            !is_int($city['id'])
            || !is_string($city['name'])
            || !array_key_exists('region', $city)
            || ($city['region'] !== null && !is_string($city['region']))
            || !is_float($city['lat'])
            || !is_float($city['lng'])
            || !is_int($city['population'])
        ) {
            return false;
        }
    }

    return true;
})($korea));
check('KR cities are sorted by population', (static function (array $cities): bool {
    for ($i = 1; $i < count($cities); $i++) {
        if ($cities[$i - 1]['population'] < $cities[$i]['population']) {
            return false;
        }
    }

    return true;
})($korea));

$aruba = $catalog->cities('AW');

check('null region is preserved', $aruba !== [] && array_key_exists('region', $aruba[0]) && $aruba[0]['region'] === null);

$seoul = $catalog->findCity('KR', 1835848);

check('findCity KR/1835848 returns Seoul', is_array($seoul) && $seoul['name'] === 'Seoul');
checkClose('Seoul latitude', $seoul['lat'] ?? 0.0, 37.566, 0.001);
checkClose('Seoul longitude', $seoul['lng'] ?? 0.0, 126.9784, 0.001);
check('findCity rejects a city from another country', $catalog->findCity('US', 1835848) === null);
check('findCity rejects an unknown city id', $catalog->findCity('KR', 999999999) === null);
check('findCity rejects a non-positive city id', $catalog->findCity('KR', 0) === null);
check('findCity rejects an invalid country code', $catalog->findCity('../cities/KR', 1835848) === null);

$totalCities = 0;
$emptyCountries = [];

foreach ($countries as $country) {
    $count = count($catalog->cities($country['code']));

    if ($count === 0) {
        $emptyCountries[] = $country['code'];
    }

    $totalCities += $count;
}

check(
    'total city count matches version.json',
    $totalCities === (int) $version['city_count'],
    $totalCities . ' vs ' . $version['city_count']
);
check('every country has at least one city', $emptyCountries === [], implode(', ', $emptyCountries));

echo PHP_EOL . 'Dataset errors' . PHP_EOL;

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nj_shipping_' . bin2hex(random_bytes(4));
mkdir($tempDir . DIRECTORY_SEPARATOR . 'cities', 0777, true);

try {
    expectRuntimeException(
        'missing countries.json throws',
        static fn () => (new DestinationCatalog($tempDir))->countries()
    );

    file_put_contents($tempDir . '/countries.json', '{not json');
    expectRuntimeException(
        'invalid countries.json throws',
        static fn () => (new DestinationCatalog($tempDir))->countries()
    );

    file_put_contents($tempDir . '/countries.json', json_encode(['KR' => 'South Korea']));
    expectRuntimeException(
        'non-list countries.json throws',
        static fn () => (new DestinationCatalog($tempDir))->countries()
    );

    file_put_contents($tempDir . '/countries.json', json_encode([['code' => 'ZZ', 'name' => 'Test']]));
    expectRuntimeException(
        'missing country city file throws',
        static fn () => (new DestinationCatalog($tempDir))->cities('ZZ')
    );

    file_put_contents($tempDir . '/cities/ZZ.json', '[{"id": "oops"}]');
    expectRuntimeException(
        'malformed city row throws',
        static fn () => (new DestinationCatalog($tempDir))->cities('ZZ')
    );
} finally {
    @unlink($tempDir . '/cities/ZZ.json');
    @unlink($tempDir . '/countries.json');
    @rmdir($tempDir . '/cities');
    @rmdir($tempDir);
}

$rates = ['base_fee_usd' => '3.00', 'fee_per_km_usd' => '0.0015'];
$calculator = new ShippingCalculator($rates);
$origin = $calculator->origin();

echo PHP_EOL . 'Calculator' . PHP_EOL;

check('numeric string rates configure the calculator', $calculator->isConfigured());
check('origin name comes from config', $origin['name'] === 'Seoul, South Korea', $origin['name']);
checkClose('origin latitude', $origin['latitude'], 37.5665, 0.0001);
checkClose('origin longitude', $origin['longitude'], 126.978, 0.0001);
checkClose('distance to the same point is zero', $calculator->distanceKm(37.5665, 126.978, 37.5665, 126.978), 0.0, 0.0001);
checkClose(
    'distance is symmetric',
    $calculator->distanceKm(37.5665, 126.978, 35.6895, 139.69171),
    $calculator->distanceKm(35.6895, 139.69171, 37.5665, 126.978),
    0.0001
);

$destinations = [
    ['JP', 1850147, 'Tokyo', 1152.62, 4.73],
    ['ID', 1642911, 'Jakarta', 5296.88, 10.95],
    ['US', 5368361, 'Los Angeles', 9585.81, 17.38],
    ['GB', 2643743, 'London', 8857.18, 16.29],
];

foreach ($destinations as [$code, $id, $name, $expectedDistance, $expectedShipping]) {
    $city = $catalog->findCity($code, $id);

    check('dataset contains ' . $name, is_array($city));

    if (!is_array($city)) {
        continue;
    }

    $distance = $calculator->distanceKm($origin['latitude'], $origin['longitude'], $city['lat'], $city['lng']);
    $quote = $calculator->quote($city['lat'], $city['lng']);

    checkClose('Seoul -> ' . $name . ' distance', $distance, $expectedDistance, 10.0);
    check('quote for ' . $name . ' succeeds', is_array($quote));

    if (!is_array($quote)) {
        continue;
    }

    checkClose($name . ' quote distance', $quote['distance_km'], $expectedDistance, 10.0);
    checkClose($name . ' quote shipping', $quote['shipping_usd'], $expectedShipping, 0.03);
}

$seoulQuote = $calculator->quote((float) ($seoul['lat'] ?? 0.0), (float) ($seoul['lng'] ?? 0.0));

check('Seoul quote succeeds', is_array($seoulQuote));
checkClose('Seoul quote distance', $seoulQuote['distance_km'] ?? 0.0, 0.07, 0.01);
checkClose('Seoul quote shipping', $seoulQuote['shipping_usd'] ?? 0.0, 3.0, 0.005);
check('quote currency is USD', ($seoulQuote['currency'] ?? '') === 'USD');

check('quote rejects latitude above 90', $calculator->quote(90.1, 0.0) === null);
check('quote rejects latitude below -90', $calculator->quote(-90.1, 0.0) === null);
check('quote rejects longitude above 180', $calculator->quote(0.0, 180.1) === null);
check('quote accepts boundary coordinates', $calculator->quote(90.0, 180.0) !== null);

$freeCalculator = new ShippingCalculator(['base_fee_usd' => '0', 'fee_per_km_usd' => '0']);
check('zero rates are considered configured', $freeCalculator->isConfigured());
checkClose(
    'zero rates produce zero shipping',
    $freeCalculator->quote(35.6895, 139.69171)['shipping_usd'] ?? -1.0,
    0.0,
    0.001
);

$customOrigin = new ShippingCalculator($rates, ['name' => 'Test', 'latitude' => 0.0, 'longitude' => 0.0]);
check('custom origin is injected', $customOrigin->origin()['name'] === 'Test');
checkClose(
    'custom origin changes the distance',
    $customOrigin->quote(0.0, 1.0)['distance_km'] ?? 0.0,
    111.19,
    0.5
);

echo PHP_EOL . 'Unconfigured rates' . PHP_EOL;

$unconfigured = [
    'both rates missing' => ['base_fee_usd' => null, 'fee_per_km_usd' => null],
    'base fee blank' => ['base_fee_usd' => '', 'fee_per_km_usd' => '0.0015'],
    'distance fee missing' => ['base_fee_usd' => '3.00'],
    'negative base fee' => ['base_fee_usd' => '-3.00', 'fee_per_km_usd' => '0.0015'],
    'negative distance fee' => ['base_fee_usd' => '3.00', 'fee_per_km_usd' => '-0.0015'],
    'non-numeric distance fee' => ['base_fee_usd' => '3.00', 'fee_per_km_usd' => 'abc'],
];

foreach ($unconfigured as $label => $invalidRates) {
    $invalid = new ShippingCalculator($invalidRates);

    check($label . ' is not configured', !$invalid->isConfigured());
    check($label . ' returns a null quote', $invalid->quote(35.6895, 139.69171) === null);
}

echo PHP_EOL;
echo $failures === 0
    ? 'OK (' . $checks . ' checks)' . PHP_EOL
    : 'FAILED (' . $failures . ' of ' . $checks . ' checks)' . PHP_EOL;

exit($failures === 0 ? 0 : 1);
