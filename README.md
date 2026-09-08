# quineworks/taxlane

Official PHP SDK for [TaxLane](https://taxlane.ng)'s free
[Developer API](https://taxlane.ng/developers) — PAYE, VAT, Withholding Tax,
Companies Income Tax, Capital Gains Tax, and 6 more Nigeria tax calculators,
as static method calls instead of hand-written `curl`.

- **Zero Composer dependencies** — only the `ext-curl` PHP extension, which
  ships with essentially every PHP install (shared hosting, Laravel
  Forge/Vapor, Docker's official `php` images). No Guzzle.
- **A single static facade**, `TaxLane\TaxLane` — one `camelCase` method per
  endpoint, each taking the request body as a plain associative array (the
  real camelCase JSON keys, no translation layer) so a call can never drift
  from the live API's request shape.
- **No API key, no account, no rate-limit tiers to configure** — the API
  itself has none.

Every method returns the parsed JSON response as a plain associative array,
not a generated class/DTO — see [Response shape](#response-shape) below for
why.

## Install

```sh
composer require quineworks/taxlane
```

If this package hasn't been published to Packagist yet (see
[Distribution](#distribution) below): unlike npm/pip, Composer has no
built-in "install from a subdirectory of a git repo" — a `vcs`-type
repository only looks for `composer.json` at the target repo's root, and
this package's lives at `sdk-php/composer.json`, not `tax-lane/composer.json`
(this monorepo has no root `composer.json` of its own — the app itself is a
Node/Vite project). Clone this repo and point Composer's `path` repository
type at the subdirectory instead:

```sh
git clone https://github.com/quineworks/tax-lane.git ../tax-lane
composer config repositories.taxlane path ../tax-lane/sdk-php
composer require quineworks/taxlane:@dev
```

## Quickstart

```php
use TaxLane\TaxLane;

$result = TaxLane::calculatePaye([
    'grossAnnualIncome' => 6_000_000,
    'annualRentPaid' => 1_200_000,
]);
// ['taxableIncome' => 5760000, 'payeTax' => 826800, 'annualTax' => 826800, 'monthlyTax' => 68900,
//  'reliefs' => [...], 'bandBreakdown' => [...]]
```

Every method throws `TaxLane\TaxLaneApiException` (a `\RuntimeException` --
`getMessage()`/`getCode()` carry the API's error string/HTTP status) on a
non-200 response, and accepts an optional trailing `$baseUrl` argument to
point at a local/staging API instead of the production default:

```php
use TaxLane\TaxLane;
use TaxLane\TaxLaneApiException;

try {
    TaxLane::calculatePaye(['grossAnnualIncome' => 'not a number'], 'http://localhost:3000');
} catch (TaxLaneApiException $e) {
    echo $e->getCode() . ' ' . $e->getMessage(); // 400 "grossAnnualIncome is required and must be a number"
}
```

## Endpoints

### `calculatePaye`

```php
use TaxLane\TaxLane;

TaxLane::calculatePaye(['grossAnnualIncome' => 6_000_000, 'annualRentPaid' => 1_200_000]);
// [
//   'taxableIncome' => 5760000,
//   'payeTax' => 826800,
//   'annualTax' => 826800,
//   'monthlyTax' => 68900,
//   'reliefs' => ['rentRelief' => 240000, 'pensionRelief' => 0, 'nhfRelief' => 0, 'nhisRelief' => 0, 'lifeAssurancePremiumRelief' => 0, 'minimumWageExempt' => false, 'taxableTerminationBenefit' => 0],
//   'bandBreakdown' => [
//     ['from' => 800000, 'to' => 3000000, 'rate' => 0.15, 'taxInBand' => 330000],
//     ['from' => 3000000, 'to' => 5760000, 'rate' => 0.18, 'taxInBand' => 496800],
//   ],
// ]
```

Only `grossAnnualIncome` is required — every other field defaults to `0`.

### `calculatePayrollBatch`

```php
use TaxLane\TaxLane;

TaxLane::calculatePayrollBatch([
    'employees' => [
        ['grossAnnualIncome' => 6_000_000],
        ['grossAnnualIncome' => 900_000, 'annualRentPaid' => 400_000],
    ],
]);
// ['results' => [['taxableIncome' => 6000000, ...], ['taxableIncome' => 820000, ...]]]
```

Calculates PAYE for up to 1,000 employees in one request — `results[i]` is
exactly what `calculatePaye($employees[i])` would return, in the same
order. An invalid item throws with an `employees[i].`-prefixed message;
there's no partial-success mode.

### `calculateVat`

```php
TaxLane::calculateVat(['amount' => 100_000, 'mode' => 'exclusive']);
// ['net' => 100000, 'vat' => 7500, 'gross' => 107500]
```

### `calculatePresumptive`

```php
TaxLane::calculatePresumptive(['turnover' => 20_000_000]);
// ['turnover' => 20000000, 'taxOwed' => 200000, 'belowFloor' => false, 'monthlySetAside' => 16666.666666666668, 'turnoverToFloor' => 0]
```

### `calculateDirectAssessment`

```php
TaxLane::calculateDirectAssessment([
    'turnover' => 10_000_000,
    'allowableExpenses' => 3_000_000,
    'qualifyingCapitalExpenditure' => 1_000_000,
    'capitalAllowanceAssetClass' => 'plantAgriculturalEquipmentFurniture',
]);
// ['turnover' => 10000000, 'allowableExpenses' => 3000000, 'capitalAllowance' => 200000, 'taxableProfit' => 6800000, 'taxOwed' => 1014000, 'effectiveRate' => 0.14911764705882352]
```

### `calculateCit`

```php
TaxLane::calculateCit([
    'turnover' => 30_000_000,
    'fixedAssets' => 100_000_000,
    'isProfessionalServices' => false,
    'taxableProfit' => 5_000_000,
    'assessableProfit' => 8_000_000,
]);
// [
//   'eligibility' => ['turnover' => 30000000, 'fixedAssets' => 100000000, 'isProfessionalServices' => false, 'turnoverEligible' => true, 'fixedAssetsEligible' => true, 'eligible' => true, 'rate' => 0],
//   'standardRate' => ['taxableProfit' => 5000000, 'annualTax' => 1500000],
//   'developmentLevy' => ['assessableProfit' => 8000000, 'developmentLevy' => 320000],
// ]
```

`eligibility` is always returned. `standardRate` is included only when
`taxableProfit` is sent; `developmentLevy` is included only when
`assessableProfit` is sent — the three are independently computed.

### `calculateWht`

```php
TaxLane::calculateWht(['transactionType' => 'professionalFees', 'amount' => 1_000_000, 'mode' => 'gross']);
// ['transactionType' => 'professionalFees', 'mode' => 'gross', 'amount' => 1000000, 'rate' => 0.05, 'gross' => 1000000, 'withheld' => 50000, 'net' => 950000]
```

### `calculateEmployerCost`

```php
TaxLane::calculateEmployerCost([
    'annualBasicSalary' => 6_000_000,
    'annualHousingAllowance' => 1_000_000,
    'annualTransportAllowance' => 500_000,
]);
// ['annualGrossPay' => 7500000, 'pensionablePay' => 7500000, 'employerPensionContribution' => 750000, ..., 'employeePaye' => [...]]
```

### `calculateCgt`

```php
TaxLane::calculateCgt([
    'taxpayerType' => 'individual',
    'assetType' => 'property',
    'disposalProceeds' => 50_000_000,
    'costBase' => 30_000_000,
]);
// ['chargeableGain' => 20000000, 'exempt' => false, 'cgtLiability' => 3630000]
```

`digitalAssetCategory` is required when `assetType` is `'digital-asset'`,
ignored otherwise.

### `calculateStampDuty`

```php
TaxLane::calculateStampDuty(['instrumentType' => 'tenancy', 'amount' => 12_000_000]);
// ['instrumentType' => 'tenancy', 'amount' => 12000000, 'rate' => 0.0078, 'duty' => 93600, 'exempt' => false]
```

### `calculateImportDuty`

```php
TaxLane::calculateImportDuty(['fob' => 5_000_000, 'freight' => 300_000, 'insurance' => 100_000, 'band' => '20']);
// ['fob' => 5000000, 'freight' => 300000, 'insurance' => 100000, 'cif' => 5400000, 'band' => '20', 'dutyRate' => 0.2, 'duty' => 1080000, 'surcharge' => 75600, 'etls' => 27000, 'fcs' => 200000, 'vat' => 508695, 'totalLandedCost' => 7291295]
```

## Response shape

Every method returns the parsed JSON response body as a plain associative
array, *not* a generated readonly class/DTO. PHP has no structural-typing
import across a package boundary the way the JS/TS SDK's cross-package TS
type import does, and hand-duplicating every response shape in a third,
PHP-only schema would be exactly the kind of extra place to maintain and
drift from that SDK avoided in the first place. A plain array is also the
common pattern lightweight PHP API-wrapper packages use for response
bodies when they don't need strict typing for a stateless, no-auth JSON
POST. Each method's PHPDoc block documents the response's top-level keys
so an IDE isn't left guessing without a shape entirely.

## Errors

A missing or non-numeric/non-boolean required field, or an invalid enum
value, throws `TaxLane\TaxLaneApiException` with `getCode()` of `400` and
a `getMessage()` describing the offending field. See the full
field-by-field reference at [taxlane.ng/developers](https://taxlane.ng/developers)
or the [OpenAPI spec](https://taxlane.ng/openapi.json).

## Limits

No API key required, no daily cap. The underlying API is limited to 50
requests/second (100 burst) at the shared Gateway level to keep the service
available for everyone — this SDK does no client-side retry/backoff, since
there's no rate limit to work around on a per-caller basis.

## Distribution

Published to [Packagist](https://packagist.org) as `quineworks/taxlane`,
when a fleet Packagist publisher account exists to publish it under. Until
then, install directly from this repo's `sdk-php/` subdirectory via a local
`path` repository — see [Install](#install) above.

## License

MIT — see the repo root [`LICENSE`](../LICENSE).
