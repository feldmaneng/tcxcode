<?php
namespace App\Libraries;

/**
 * Per-request API auth context.
 *
 * Replaces the previous pattern of attaching `$request->apiAuth = [...]` directly
 * to the IncomingRequest object. PHP 8.2+ deprecates dynamic properties on classes
 * that don't declare them, which produced warnings like:
 *   "Creation of dynamic property CodeIgniter\HTTP\IncomingRequest::$apiAuth is deprecated"
 *
 * Usage:
 *   ApiAuthContext::set(['type' => 'hmac', 'client_id' => 1, ...]);
 *   $auth = ApiAuthContext::get(); // null if not set
 */
final class ApiAuthContext
{
    /** @var array<string,mixed>|null */
    private static ?array $auth = null;

    /** @param array<string,mixed> $auth */
    public static function set(array $auth): void
    {
        self::$auth = $auth;
    }

    /** @return array<string,mixed>|null */
    public static function get(): ?array
    {
        return self::$auth;
    }

    public static function clear(): void
    {
        self::$auth = null;
    }
}
