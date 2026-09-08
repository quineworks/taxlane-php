<?php

declare(strict_types=1);

// Deliberately namespaced `TaxLane`, not `TaxLane\Tests` -- PHP resolves an
// unqualified function call (curl_init(), curl_exec(), etc. in
// TaxLane::post()) against the *calling code's* declared namespace first,
// falling back to the global namespace only if no such function exists
// there (https://www.php.net/manual/en/language.namespaces.fallback.php).
// Declaring curl_init()/etc. here, in the same `TaxLane` namespace
// src/TaxLane.php uses, makes every call TaxLane::post() makes resolve to
// this stub instead of the real global curl_* functions -- the standard,
// dependency-free technique for unit-testing PHP code that calls a global
// function, without an HTTP client library or a DI container just to make
// one function call swappable in tests. Only loaded by
// phpunit.xml.dist's bootstrap, never part of the package's own
// PSR-4-autoloaded `src/`.
namespace TaxLane;

/**
 * Configures the curl_* stub below and exposes what request it captured.
 * Call CurlStub::reset() in each test's tearDown so state can't leak
 * between tests.
 */
final class CurlStub
{
    /** @var array{status: int, body: string}|null */
    private static ?array $response = null;
    private static ?string $error = null;
    /** @var array{url: string, body: string}|null */
    private static ?array $request = null;

    public static function respond(int $status, string $body): void
    {
        self::$response = ['status' => $status, 'body' => $body];
        self::$error = null;
    }

    public static function fail(string $error): void
    {
        self::$error = $error;
        self::$response = null;
    }

    /** @return array{url: string, body: string}|null */
    public static function request(): ?array
    {
        return self::$request;
    }

    public static function reset(): void
    {
        self::$response = null;
        self::$error = null;
        self::$request = null;
    }

    /** @internal used by the curl_* stub functions below */
    public static function recordRequest(string $url, string $body): void
    {
        self::$request = ['url' => $url, 'body' => $body];
    }

    /** @internal used by the curl_* stub functions below */
    public static function pendingError(): ?string
    {
        return self::$error;
    }

    /** @internal used by the curl_* stub functions below */
    public static function pendingResponse(): ?array
    {
        return self::$response;
    }
}

function curl_init(string $url)
{
    CurlStub::recordRequest($url, '');
    return 'taxlane-stub-handle';
}

/** @param array<int, mixed> $options */
function curl_setopt_array($ch, array $options): bool
{
    $request = CurlStub::request();
    if ($request !== null && array_key_exists(\CURLOPT_POSTFIELDS, $options)) {
        CurlStub::recordRequest($request['url'], (string) $options[\CURLOPT_POSTFIELDS]);
    }
    return true;
}

function curl_exec($ch)
{
    if (CurlStub::pendingError() !== null) {
        return false;
    }
    return CurlStub::pendingResponse()['body'] ?? '';
}

function curl_error($ch): string
{
    return CurlStub::pendingError() ?? '';
}

function curl_getinfo($ch, int $option)
{
    return CurlStub::pendingResponse()['status'] ?? 0;
}

function curl_close($ch): void
{
}
