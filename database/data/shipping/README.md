# Shipping Destination Dataset

Local, versioned city-coordinate data used to estimate simulated shipping from
Seoul, South Korea. The runtime application reads these JSON files only; it
never downloads GeoNames data.

## Source

- Dataset: [GeoNames](https://www.geonames.org/)
- Download source: <https://download.geonames.org/export/dump/>
- Dumps used: `cities15000`, `countryInfo.txt`, `admin1CodesASCII.txt`
- Supplementary per-country dumps: `PW`, `TK`
- Source dump date: **2026-11-16**
- License: **Creative Commons Attribution 4.0 International (CC BY 4.0)**
  (<https://creativecommons.org/licenses/by/4.0/>)

Attribution: *Contains data from GeoNames, licensed under CC BY 4.0.*

## Files

```text
database/data/shipping/
  countries.json        # country code + display name, sorted by name
  cities/{ISO2}.json    # one file per supported country
  version.json          # source, date, license, filter, counts, exceptions
  README.md
  LICENSE
```

Each city row contains:

```json
{
  "id": 1835848,
  "name": "Seoul",
  "region": "Seoul",
  "lat": 37.566,
  "lng": 126.9784,
  "population": 10349312
}
```

- `id` is the GeoNames ID and is unique within a country.
- `region` is resolved from `admin1CodesASCII.txt`; it is `null` when the
  source has no matching admin-1 entry.
- Coordinates are copied unchanged from GeoNames.
- Cities are sorted by population descending, then name ascending.

## Counts

`version.json` is authoritative. The current build contains:

- **244 countries**
- **34,224 cities**

## Filter criteria

- GeoNames feature class `P` (populated place) only.
- Officially assigned ISO 3166-1 alpha-2 country codes only.
- Population greater than `0`.
- Duplicate GeoNames IDs within a country are collapsed.
- `XK` (Kosovo) is excluded because it is not an officially assigned ISO
  3166-1 alpha-2 code.
- Withdrawn codes `AN` (Netherlands Antilles) and `CS` (Serbia and Montenegro)
  are excluded.

## Coverage exceptions

`cities15000` does not contain a populated place for every official country.
Countries with no city in that dump are supplemented from a GeoNames
per-country dump when one has usable populated places:

- `PW` (Palau) — `cities15000` has no qualifying row; populated places such as
  Koror come from the `PW` country dump.
- `TK` (Tokelau) — no row in `cities15000`; populated places come from the
  `TK` country dump.

The following official codes are intentionally omitted because they have no
permanent population:

- `AQ` — Antarctica
- `BV` — Bouvet Island
- `HM` — Heard Island and McDonald Islands
- `IO` — British Indian Ocean Territory
- `UM` — United States Minor Outlying Islands

The build fails if any other supported country would end up with no cities, so
a future source change cannot silently remove a destination.

## Rebuild

Raw GeoNames files are **not committed**. Download and extract them into the
ignored `raw/` directory, then run the generator:

```powershell
New-Item -ItemType Directory -Force database/data/shipping/raw/supplement

curl.exe -L --fail -o database/data/shipping/raw/countryInfo.txt https://download.geonames.org/export/dump/countryInfo.txt
curl.exe -L --fail -o database/data/shipping/raw/admin1CodesASCII.txt https://download.geonames.org/export/dump/admin1CodesASCII.txt
curl.exe -L --fail -o database/data/shipping/raw/cities15000.zip https://download.geonames.org/export/dump/cities15000.zip
tar -xf database/data/shipping/raw/cities15000.zip -C database/data/shipping/raw

curl.exe -L --fail -o database/data/shipping/raw/PW.zip https://download.geonames.org/export/dump/PW.zip
tar -xf database/data/shipping/raw/PW.zip -C database/data/shipping/raw/supplement
curl.exe -L --fail -o database/data/shipping/raw/TK.zip https://download.geonames.org/export/dump/TK.zip
tar -xf database/data/shipping/raw/TK.zip -C database/data/shipping/raw/supplement

php database/build-shipping-data.php --source-date=2026-11-16
```

`--source-date` should be the GeoNames dump date (the `Last-Modified` value of
`cities15000.zip`), not the date of the local rebuild. Other paths can be
overridden with `--cities`, `--countries`, `--admin1`, `--supplement-dir`, and
`--output`. Run `php database/build-shipping-data.php --help` for the list.

## Modifications

The source data was transformed for this project:

- Filtered to the criteria above.
- Reduced to the fields listed above.
- Country names resolved from `countryInfo.txt`.
- Region names resolved from `admin1CodesASCII.txt`.
- Serialized as JSON and sorted deterministically.

No coordinates, city names, or populations were modified.
