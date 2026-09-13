<?php

declare(strict_types=1);

namespace TaxLane;

/**
 * Thrown (not returned) on any non-200 response from the Developer API --
 * matches the ergonomics Paystack's/Flutterwave's own SDKs use, and mirrors
 * sdk/src/error.ts's TaxLaneApiError and sdk-python's TaxLaneApiError
 * (docs/product/developer-api-php-sdk.md). Reuses RuntimeException's own
 * getMessage()/getCode() rather than inventing custom accessors: getMessage()
 * is the API's `error` string, getCode() is the HTTP status.
 */
final class TaxLaneApiException extends \RuntimeException
{
    private ?int $retryAfter;

    public function __construct(string $message, int $code, ?int $retryAfter = null)
    {
        parent::__construct($message, $code);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Only non-null on the Lambda's own 429 (which sets `Retry-After: 1`,
     * see PR#1886) -- every other non-200 response has no such header, so
     * this defaults to null rather than a bogus 0 (tax-lane#1889).
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
