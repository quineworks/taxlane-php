<?php

declare(strict_types=1);

namespace TaxLane;

/**
 * Official PHP SDK for TaxLane's free Developer API (tax-lane#1722, spec
 * docs/product/developer-api-php-sdk.md). One static method per /v1/*
 * endpoint, matching the JS/TS SDK's (sdk/) 11 function names 1:1 in
 * camelCase form -- see README.md for a quickstart per endpoint.
 *
 * A static facade rather than an instantiated client is the deliberate
 * PHP-idiomatic choice here (see the spec's "What ships"): this API has no
 * API key/config to hold as instance state, and TaxLane::calculatePaye(...)
 * reads as idiomatic to a Laravel developer without any actual Laravel
 * coupling.
 *
 * Every method takes the request body as a single associative array (the
 * real camelCase JSON keys, no snake_case translation layer) plus an
 * optional trailing $baseUrl, and returns the parsed JSON response as a
 * plain associative array -- not a generated class/DTO, since PHP has no
 * structural-typing import across a package boundary to generate one from
 * without a second schema to drift from. Each method's docblock documents
 * the response's top-level keys, sourced from developersContent.tsx's
 * *_RESPONSE examples.
 *
 * Unknown/misspelled request keys are passed straight through to the API
 * and surfaced via that endpoint's existing 400 validation, not caught
 * client-side.
 */
final class TaxLane
{
    // Matches web/src/lib/api/config.ts's production default -- not
    // imported from there directly since that constant lives in the web
    // app's own build, not this standalone package (mirrors
    // sdk/src/client.ts's and sdk-python's own comment).
    public const API_BASE_URL = 'https://api.taxlane.ng';

    private function __construct()
    {
        // Static-only facade -- no instance state, see class docblock.
    }

    /**
     * Calculates PAYE tax exactly the way POST /v1/paye does. Only
     * `grossAnnualIncome` is required -- every other field defaults to 0.
     *
     * @param array<string, mixed> $input `grossAnnualIncome`,
     *     `secondaryAnnualIncome`, `annualRentPaid`, `terminationBenefit`,
     *     `pensionContribution`, `nhfContribution`, `nhisContribution`,
     *     `lifeAssurancePremiumContribution`.
     * @return array<string, mixed> `taxableIncome`, `payeTax`, `annualTax`,
     *     `monthlyTax`, a `reliefs` breakdown, and a `bandBreakdown` array.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculatePaye(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/paye', $input, $baseUrl);
    }

    /**
     * Calculates PAYE for up to 1,000 employees in one request --
     * `results[i]` is exactly what calculatePaye() would return for
     * `employees[i]`, in the same order. An invalid item 400s the whole
     * batch, prefixed `employees[i].` so you can find the failing row;
     * there is no partial-success mode.
     *
     * @param array{employees: array<int, array<string, mixed>>} $input
     * @return array{results: array<int, array<string, mixed>>}
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculatePayrollBatch(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/payroll/batch', $input, $baseUrl);
    }

    /**
     * Calculates VAT exactly the way POST /v1/vat does, at the standard
     * 7.5% rate.
     *
     * @param array<string, mixed> $input `amount`, `mode`
     *     ("exclusive"|"inclusive"), `zeroRated`.
     * @return array<string, mixed> `net`, `vat`, `gross`.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateVat(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/vat', $input, $baseUrl);
    }

    /**
     * Calculates Presumptive Tax exactly the way POST /v1/presumptive
     * does. `turnover` is the only field.
     *
     * @param array<string, mixed> $input `turnover`.
     * @return array<string, mixed> `turnover`, `taxOwed`, `belowFloor`,
     *     `monthlySetAside`, `turnoverToFloor`.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculatePresumptive(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/presumptive', $input, $baseUrl);
    }

    /**
     * Calculates Direct Assessment tax exactly the way POST
     * /v1/direct-assessment does. `turnover` and `allowableExpenses` are
     * required; capital allowance fields default to no capital
     * expenditure.
     *
     * @param array<string, mixed> $input `turnover`, `allowableExpenses`,
     *     `qualifyingCapitalExpenditure`, `capitalAllowanceAssetClass`.
     * @return array<string, mixed> `turnover`, `allowableExpenses`,
     *     `capitalAllowance`, `taxableProfit`, `taxOwed`, `effectiveRate`.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateDirectAssessment(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/direct-assessment', $input, $baseUrl);
    }

    /**
     * Calculates Companies Income Tax exactly the way POST /v1/cit does.
     * `standardRate` is included only when `taxableProfit` is sent;
     * `developmentLevy` is included only when `assessableProfit` is sent --
     * the three are independently computed, not a single pipeline.
     *
     * @param array<string, mixed> $input `turnover`, `fixedAssets`,
     *     `isProfessionalServices`, `taxableProfit`, `assessableProfit`.
     * @return array<string, mixed> `eligibility` always; `standardRate` and
     *     `developmentLevy` only when their triggering field was sent.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateCit(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/cit', $input, $baseUrl);
    }

    /**
     * Calculates Withholding Tax exactly the way POST /v1/wht does. Set
     * `mode` to "gross" if `amount` is the invoice figure, or "net" if
     * `amount` is the figure the payee should receive after withholding.
     *
     * @param array<string, mixed> $input `transactionType`, `amount`,
     *     `mode` ("gross"|"net").
     * @return array<string, mixed> `transactionType`, `mode`, `amount`,
     *     `rate`, `gross`, `withheld`, `net`.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateWht(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/wht', $input, $baseUrl);
    }

    /**
     * Calculates total employer cost exactly the way POST
     * /v1/employer-cost does. Only `annualBasicSalary` is required --
     * every allowance defaults to 0.
     *
     * @param array<string, mixed> $input `annualBasicSalary`,
     *     `annualHousingAllowance`, `annualTransportAllowance`,
     *     `annualOtherAllowances`, `annualRentPaid`.
     * @return array<string, mixed> `annualGrossPay`, `pensionablePay`, the
     *     four statutory employer contributions,
     *     `totalStatutoryEmployerCost`, and a nested `employeePaye` object
     *     (the same shape calculatePaye() returns, informational only).
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateEmployerCost(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/employer-cost', $input, $baseUrl);
    }

    /**
     * Calculates Capital Gains Tax exactly the way POST /v1/cgt does.
     * `digitalAssetCategory` is required when `assetType` is
     * "digital-asset", and ignored otherwise -- every other conditional
     * field is optional and only applies to its matching
     * `assetType`/`taxpayerType`.
     *
     * @param array<string, mixed> $input `taxpayerType`, `assetType`,
     *     `disposalProceeds`, `costBase`, `isPrincipalPrivateResidence`,
     *     `isFullyReinvestedInNigerianShares`,
     *     `isRegulatedSecuritiesLendingTransfer`, `digitalAssetCategory`,
     *     `otherAnnualTaxableIncome`, `isSmallCompany`.
     * @return array<string, mixed> `chargeableGain`, `exempt`,
     *     `exemptReason` (only when exempt), `cgtLiability`, and
     *     `vaWithholdingEstimate` (digital assets only, categories with a
     *     1% disposal withholding).
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateCgt(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/cgt', $input, $baseUrl);
    }

    /**
     * Calculates Stamp Duty exactly the way POST /v1/stamp-duty does.
     *
     * @param array<string, mixed> $input `instrumentType`, `amount`.
     * @return array<string, mixed> `instrumentType`, `amount`, `rate`
     *     (null for the two fixed-fee instrument types), `duty`, `exempt`.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateStampDuty(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/stamp-duty', $input, $baseUrl);
    }

    /**
     * Calculates import duty and landed cost exactly the way POST
     * /v1/import-duty does. Pass `freight`/`insurance` as 0 (their
     * default) if you already have a combined CIF value in `fob`.
     *
     * @param array<string, mixed> $input `fob`, `freight`, `insurance`,
     *     `band`, `customDutyRatePercent`.
     * @return array<string, mixed> `fob`, `freight`, `insurance`, `cif`,
     *     `band`, `dutyRate`, `duty`, `surcharge`, `etls`, `fcs`, `vat`,
     *     `totalLandedCost`.
     * @throws TaxLaneApiException on a non-200 response.
     */
    public static function calculateImportDuty(array $input, ?string $baseUrl = null): array
    {
        return self::post('/v1/import-duty', $input, $baseUrl);
    }

    /**
     * No retries/timeout/auth -- the API has none of those to work around
     * (see the spec's "Explicit non-goals"). A network/DNS failure throws
     * curl's own error, uncaught here, rather than being swallowed or
     * wrapped.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private static function post(string $path, array $body, ?string $baseUrl): array
    {
        $url = ($baseUrl ?? self::API_BASE_URL) . $path;
        $json = json_encode($body, JSON_THROW_ON_ERROR);

        // curl_* below are called unqualified so tests/CurlStub.php can
        // shadow them for this namespace only -- PHP resolves an
        // unqualified function call against the calling code's own
        // namespace first, falling back to the global namespace only if no
        // such function is defined there. See CurlStub.php's own docblock.
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => $json,
            \CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            \CURLOPT_RETURNTRANSFER => true,
        ]);

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("TaxLane request failed: {$error}");
        }

        $status = (int) curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);

        /** @var mixed $decoded */
        $decoded = json_decode((string) $responseBody, true);

        if ($status !== 200) {
            $message = (is_array($decoded) && isset($decoded['error']))
                ? (string) $decoded['error']
                : (string) $responseBody;
            throw new TaxLaneApiException($message, $status);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
