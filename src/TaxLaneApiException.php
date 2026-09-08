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
}
