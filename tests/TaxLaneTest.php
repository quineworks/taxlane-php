<?php

declare(strict_types=1);

namespace TaxLane\Tests;

use PHPUnit\Framework\TestCase;
use TaxLane\CurlStub;
use TaxLane\TaxLane;
use TaxLane\TaxLaneApiException;

final class TaxLaneTest extends TestCase
{
    protected function tearDown(): void
    {
        CurlStub::reset();
    }

    /**
     * One row per DEVELOPER_API_TOC endpoint (web/src/lib/content/
     * developersContent.tsx), matching the JS/Python SDKs' own endpoint
     * table -- deliberately 11, no more no less, since PHP's
     * associative-array-pass-through design (docs/product/
     * developer-api-php-sdk.md) needs no per-endpoint field-mapping
     * verification the way the Python SDK's kwarg-mapping table does.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function endpoints(): array
    {
        return [
            'calculatePaye' => ['calculatePaye', '/v1/paye', ['grossAnnualIncome' => 6_000_000]],
            'calculatePayrollBatch' => [
                'calculatePayrollBatch',
                '/v1/payroll/batch',
                ['employees' => [['grossAnnualIncome' => 6_000_000]]],
            ],
            'calculateVat' => ['calculateVat', '/v1/vat', ['amount' => 100_000, 'mode' => 'exclusive']],
            'calculatePresumptive' => ['calculatePresumptive', '/v1/presumptive', ['turnover' => 20_000_000]],
            'calculateDirectAssessment' => [
                'calculateDirectAssessment',
                '/v1/direct-assessment',
                ['turnover' => 10_000_000, 'allowableExpenses' => 3_000_000],
            ],
            'calculateCit' => [
                'calculateCit',
                '/v1/cit',
                ['turnover' => 30_000_000, 'fixedAssets' => 100_000_000, 'isProfessionalServices' => false],
            ],
            'calculateWht' => [
                'calculateWht',
                '/v1/wht',
                ['transactionType' => 'professionalFees', 'amount' => 1_000_000, 'mode' => 'gross'],
            ],
            'calculateEmployerCost' => [
                'calculateEmployerCost',
                '/v1/employer-cost',
                ['annualBasicSalary' => 6_000_000, 'annualHousingAllowance' => 1_000_000],
            ],
            'calculateCgt' => [
                'calculateCgt',
                '/v1/cgt',
                [
                    'taxpayerType' => 'individual',
                    'assetType' => 'property',
                    'disposalProceeds' => 50_000_000,
                    'costBase' => 30_000_000,
                ],
            ],
            'calculateStampDuty' => [
                'calculateStampDuty',
                '/v1/stamp-duty',
                ['instrumentType' => 'tenancy', 'amount' => 12_000_000],
            ],
            'calculateImportDuty' => [
                'calculateImportDuty',
                '/v1/import-duty',
                ['fob' => 5_000_000, 'freight' => 300_000, 'insurance' => 100_000, 'band' => '20'],
            ],
        ];
    }

    /** @dataProvider endpoints */
    public function testPostsJsonToApiBaseUrlPlusPathWithTheExactBodyPassed(
        string $method,
        string $path,
        array $input,
    ): void {
        CurlStub::respond(200, '{}');

        call_user_func([TaxLane::class, $method], $input);

        $request = CurlStub::request();
        $this->assertNotNull($request);
        $this->assertSame(TaxLane::API_BASE_URL . $path, $request['url']);
        $this->assertSame($input, json_decode($request['body'], true));
    }

    /** @dataProvider endpoints */
    public function testResolvesWithDataWrappingTheParsedJsonBodyOnA200Response(
        string $method,
        string $path,
        array $input,
    ): void {
        $body = ['taxableIncome' => 6_000_000, 'payeTax' => 870_000];
        CurlStub::respond(200, json_encode($body));

        $response = call_user_func([TaxLane::class, $method], $input);

        $this->assertSame($body, $response['data']);
        $this->assertSame(['limit' => 50, 'remaining' => 49, 'reset' => 1_757_750_400], $response['rateLimit']);
    }

    /** @dataProvider endpoints */
    public function testParsesTheRateLimitHeadersOffTheTwoHundredResponse(
        string $method,
        string $path,
        array $input,
    ): void {
        CurlStub::respond(200, '{}', [
            'X-RateLimit-Limit' => '50',
            'X-RateLimit-Remaining' => '12',
            'X-RateLimit-Reset' => '1757750461',
        ]);

        $response = call_user_func([TaxLane::class, $method], $input);

        $this->assertSame(['limit' => 50, 'remaining' => 12, 'reset' => 1_757_750_461], $response['rateLimit']);
    }

    public function testParsesTheRateLimitHeadersCaseInsensitively(): void
    {
        CurlStub::respond(200, '{}', [
            'x-ratelimit-limit' => '50',
            'X-Ratelimit-Remaining' => '7',
            'X-RATELIMIT-RESET' => '1757750500',
        ]);

        $response = TaxLane::calculatePaye(['grossAnnualIncome' => 6_000_000]);

        $this->assertSame(['limit' => 50, 'remaining' => 7, 'reset' => 1_757_750_500], $response['rateLimit']);
    }

    /** @dataProvider endpoints */
    public function testThrowsTaxLaneApiExceptionWithTheApiMessageAndStatusOnA400Response(
        string $method,
        string $path,
        array $input,
    ): void {
        CurlStub::respond(400, json_encode(['error' => 'grossAnnualIncome is required and must be a number']));

        try {
            call_user_func([TaxLane::class, $method], $input);
            $this->fail('Expected TaxLaneApiException to be thrown.');
        } catch (TaxLaneApiException $exception) {
            $this->assertSame('grossAnnualIncome is required and must be a number', $exception->getMessage());
            $this->assertSame(400, $exception->getCode());
            $this->assertNull($exception->getRetryAfter());
        }
    }

    public function testPopulatesRetryAfterFromTheHeaderOnA429Response(): void
    {
        CurlStub::respond(429, json_encode(['error' => 'Too Many Requests']), ['Retry-After' => '1']);

        try {
            TaxLane::calculatePaye(['grossAnnualIncome' => 6_000_000]);
            $this->fail('Expected TaxLaneApiException to be thrown.');
        } catch (TaxLaneApiException $exception) {
            $this->assertSame(429, $exception->getCode());
            $this->assertSame(1, $exception->getRetryAfter());
        }
    }

    public function testBaseUrlOverridesTheProductionDefault(): void
    {
        CurlStub::respond(200, '{}');

        TaxLane::calculatePaye(['grossAnnualIncome' => 6_000_000], 'http://localhost:3000');

        $this->assertSame('http://localhost:3000/v1/paye', CurlStub::request()['url']);
    }

    public function testARequestKeyNotInTheOptionalFieldsIsPassedStraightThroughUnmodified(): void
    {
        CurlStub::respond(200, '{}');

        $input = ['grossAnnualIncome' => 6_000_000, 'notARealField' => 'passed through'];
        TaxLane::calculatePaye($input);

        $this->assertSame($input, json_decode(CurlStub::request()['body'], true));
    }

    public function testANetworkFailureThrowsTheUnderlyingTransportErrorNotSwallowed(): void
    {
        CurlStub::fail('Could not resolve host: api.taxlane.ng');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not resolve host: api.taxlane.ng');

        TaxLane::calculatePaye(['grossAnnualIncome' => 6_000_000]);
    }

    public function testExportsExactlyTheElevenMethodsMatchingTheJsSdkOneToOne(): void
    {
        $expected = [
            'calculatePaye',
            'calculatePayrollBatch',
            'calculateVat',
            'calculatePresumptive',
            'calculateDirectAssessment',
            'calculateCit',
            'calculateWht',
            'calculateEmployerCost',
            'calculateCgt',
            'calculateStampDuty',
            'calculateImportDuty',
        ];

        $methods = array_values(array_filter(
            get_class_methods(TaxLane::class),
            static fn(string $name): bool => str_starts_with($name, 'calculate'),
        ));

        $this->assertCount(11, $methods);
        $this->assertSame($expected, $methods);
    }
}
