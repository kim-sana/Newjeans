<?php

declare(strict_types=1);

/**
 * Builds the local, redistributable shipping destination dataset from GeoNames
 * raw dumps.
 *
 * Usage:
 *   php database/build-shipping-data.php --source-date=2026-11-16
 *
 * All options are optional and default to the paths below. Raw downloads are
 * not committed; the runtime application only reads the generated JSON files.
 */

const SHIPPING_ISO2_CODES = [
    'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
    'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
    'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
    'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
    'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
    'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
    'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
    'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
    'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
    'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
    'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
    'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
    'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
    'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
    'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
    'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
];

const SHIPPING_INHABITED_EXCEPTION_NOTES = [
    'AQ' => 'Antarctica has no permanent population.',
    'BV' => 'Bouvet Island has no permanent population.',
    'HM' => 'Heard Island and McDonald Islands have no permanent population.',
    'IO' => 'British Indian Ocean Territory has no permanent civilian population.',
    'UM' => 'United States Minor Outlying Islands have no permanent population.',
];

/**
 * @param list<string> $argv
 * @return array<string, string>
 */
function shippingBuildOptions(array $argv): array
{
    $root = dirname(__DIR__);
    $raw = $root . '/database/data/shipping/raw';

    $defaults = [
        'cities' => $raw . '/cities15000.txt',
        'countries' => $raw . '/countryInfo.txt',
        'admin1' => $raw . '/admin1CodesASCII.txt',
        'supplement-dir' => $raw . '/supplement',
        'output' => $root . '/database/data/shipping',
        'source-date' => gmdate('Y-m-d'),
    ];

    $options = $defaults;

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--help' || $argument === '-h') {
            fwrite(STDOUT, "Usage: php database/build-shipping-data.php [--option=value]\n");
            fwrite(STDOUT, "Options:\n");

            foreach (array_keys($defaults) as $key) {
                fwrite(STDOUT, "  --{$key}\n");
            }

            exit(0);
        }

        if (!str_starts_with($argument, '--')) {
            throw new InvalidArgumentException("Unexpected argument: {$argument}");
        }

        $argument = substr($argument, 2);

        if (!str_contains($argument, '=')) {
            throw new InvalidArgumentException("Expected --option=value, got: --{$argument}");
        }

        [$key, $value] = explode('=', $argument, 2);

        if (!array_key_exists($key, $defaults)) {
            throw new InvalidArgumentException("Unknown option: --{$key}");
        }

        if ($value === '') {
            throw new InvalidArgumentException("Option --{$key} cannot be empty.");
        }

        $options[$key] = $value;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $options['source-date'])) {
        throw new InvalidArgumentException('--source-date must use YYYY-MM-DD.');
    }

    return $options;
}

/**
 * @return array<string, string> ISO2 code => country name
 */
function shippingBuildCountryNames(string $path, array $isoCodes): array
{
    $handle = shippingBuildOpen($path);
    $names = [];

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $columns = explode("\t", $line);

        if (count($columns) < 5) {
            continue;
        }

        $code = trim($columns[0]);
        $name = trim($columns[4]);

        if (!isset($isoCodes[$code]) || $name === '') {
            continue;
        }

        $names[$code] = $name;
    }

    fclose($handle);

    return $names;
}

/**
 * @return array<string, string> "CC.ADMIN1" => region name
 */
function shippingBuildAdmin1Names(string $path): array
{
    $handle = shippingBuildOpen($path);
    $names = [];

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");

        if ($line === '') {
            continue;
        }

        $columns = explode("\t", $line);

        if (count($columns) < 2) {
            continue;
        }

        $key = trim($columns[0]);
        $name = trim($columns[1]);

        if ($key !== '' && $name !== '') {
            $names[$key] = $name;
        }
    }

    fclose($handle);

    return $names;
}

/**
 * @return Generator<int, list<string>>
 */
function shippingBuildCityRows(string $path): Generator
{
    $handle = shippingBuildOpen($path);
    $firstLine = true;

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");

        if ($firstLine) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
            $firstLine = false;
        }

        if ($line === '') {
            continue;
        }

        yield explode("\t", $line);
    }

    fclose($handle);
}

/**
 * @param array<string, array<int, array<string, mixed>>> $cities
 * @param array<string, string> $admin1
 * @param array<string, true> $isoCodes
 */
function shippingBuildCollectCities(
    string $path,
    array &$cities,
    array $admin1,
    array $isoCodes
): void {
    foreach (shippingBuildCityRows($path) as $columns) {
        $city = shippingBuildCityFromColumns($columns, $admin1, $isoCodes);

        if ($city === null) {
            continue;
        }

        [$code, $id, $row] = $city;

        // GeoNames IDs are globally unique; keeping the last row for an ID
        // within a country is defensive against duplicated dump rows.
        $cities[$code][$id] = $row;
    }
}

/**
 * @param list<string> $columns
 * @param array<string, string> $admin1
 * @param array<string, true> $isoCodes
 * @return array{0: string, 1: int, 2: array<string, mixed>}|null
 */
function shippingBuildCityFromColumns(array $columns, array $admin1, array $isoCodes): ?array
{
    if (count($columns) < 19) {
        return null;
    }

    if ($columns[6] !== 'P') {
        return null;
    }

    $code = $columns[8];

    if (!isset($isoCodes[$code])) {
        return null;
    }

    $id = (int) $columns[0];
    $name = trim($columns[1]);
    $population = (int) $columns[14];

    if ($id <= 0 || $name === '' || $population <= 0) {
        return null;
    }

    $admin1Code = trim($columns[10]);
    $region = $admin1Code !== '' ? ($admin1[$code . '.' . $admin1Code] ?? null) : null;

    return [$code, $id, [
        'id' => $id,
        'name' => $name,
        'region' => $region !== '' ? $region : null,
        'lat' => (float) $columns[4],
        'lng' => (float) $columns[5],
        'population' => $population,
    ]];
}

/**
 * @param mixed $data
 */
function shippingBuildWriteJson(string $path, mixed $data): void
{
    $json = json_encode(
        $data,
        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $temporary = $path . '.tmp';

    if (file_put_contents($temporary, $json . "\n") === false) {
        throw new RuntimeException("Unable to write {$temporary}");
    }

    if (!rename($temporary, $path)) {
        @unlink($temporary);

        throw new RuntimeException("Unable to replace {$path}");
    }
}

/**
 * @return resource
 */
function shippingBuildOpen(string $path)
{
    if (!is_file($path)) {
        throw new RuntimeException("Missing source file: {$path}");
    }

    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException("Unable to open source file: {$path}");
    }

    return $handle;
}

function shippingBuildRun(array $options): void
{
    $isoCodes = array_fill_keys(SHIPPING_ISO2_CODES, true);

    $countryNames = shippingBuildCountryNames($options['countries'], $isoCodes);
    $admin1 = shippingBuildAdmin1Names($options['admin1']);

    /** @var array<string, array<int, array<string, mixed>>> $cities */
    $cities = [];
    shippingBuildCollectCities($options['cities'], $cities, $admin1, $isoCodes);

    $supplemented = [];

    if (is_dir($options['supplement-dir'])) {
        foreach (array_keys($isoCodes) as $code) {
            if (!empty($cities[$code])) {
                continue;
            }

            $supplement = $options['supplement-dir'] . '/' . $code . '.txt';

            if (!is_file($supplement)) {
                continue;
            }

            shippingBuildCollectCities($supplement, $cities, $admin1, $isoCodes);
            $supplemented[$code] = $supplement;
        }
    }

    $countries = [];
    $excluded = [];
    $cityCount = 0;

    foreach (array_keys($isoCodes) as $code) {
        $countryCities = array_values($cities[$code] ?? []);

        if ($countryCities === []) {
            $excluded[] = $code;

            continue;
        }

        if (!isset($countryNames[$code])) {
            throw new RuntimeException("No country name found for ISO2 code {$code}.");
        }

        usort($countryCities, static function (array $left, array $right): int {
            return ($right['population'] <=> $left['population'])
                ?: strcasecmp($left['name'], $right['name'])
                ?: ($left['id'] <=> $right['id']);
        });

        $cities[$code] = $countryCities;
        $cityCount += count($countryCities);

        $countries[] = [
            'code' => $code,
            'name' => $countryNames[$code],
        ];
    }

    usort($countries, static function (array $left, array $right): int {
        return strcasecmp($left['name'], $right['name']) ?: strcmp($left['code'], $right['code']);
    });

    $unexpected = array_diff($excluded, array_keys(SHIPPING_INHABITED_EXCEPTION_NOTES));

    if ($unexpected !== []) {
        throw new RuntimeException(
            'No populated cities found for: ' . implode(', ', $unexpected)
            . '. Add a per-country supplement under ' . $options['supplement-dir']
            . ' or document the exception.'
        );
    }

    $output = rtrim($options['output'], '/\\');
    $citiesDirectory = $output . '/cities';

    if (!is_dir($citiesDirectory) && !mkdir($citiesDirectory, 0775, true) && !is_dir($citiesDirectory)) {
        throw new RuntimeException("Unable to create {$citiesDirectory}");
    }

    foreach (glob($citiesDirectory . '/*.json') ?: [] as $staleFile) {
        unlink($staleFile);
    }

    foreach ($countries as $country) {
        shippingBuildWriteJson(
            $citiesDirectory . '/' . $country['code'] . '.json',
            $cities[$country['code']]
        );
    }

    shippingBuildWriteJson($output . '/countries.json', $countries);

    sort($excluded);

    $excludedWithNotes = [];

    foreach ($excluded as $code) {
        $excludedWithNotes[$code] = SHIPPING_INHABITED_EXCEPTION_NOTES[$code];
    }

    shippingBuildWriteJson($output . '/version.json', [
        'source' => 'GeoNames',
        'source_url' => 'https://download.geonames.org/export/dump/',
        'source_date' => $options['source-date'],
        'license' => 'CC BY 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
        'filter' => 'cities15000 + national capitals supplement; feature class P; official ISO 3166-1 alpha-2; population > 0',
        'country_count' => count($countries),
        'city_count' => $cityCount,
        'excluded_countries' => $excludedWithNotes,
    ]);

    fwrite(STDOUT, 'Countries: ' . count($countries) . PHP_EOL);
    fwrite(STDOUT, 'Cities: ' . $cityCount . PHP_EOL);

    if ($excluded !== []) {
        fwrite(STDOUT, 'Excluded countries: ' . implode(', ', $excluded) . PHP_EOL);
    }

    if ($supplemented !== []) {
        fwrite(STDOUT, 'Supplemented countries: ' . implode(', ', array_keys($supplemented)) . PHP_EOL);
    }
}

try {
    shippingBuildRun(shippingBuildOptions($argv));
} catch (Throwable $exception) {
    fwrite(STDERR, 'Dataset build failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
