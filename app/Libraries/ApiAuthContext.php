<?php
namespace App\Libraries;

/**
 * Per-request API auth context.
 *
 * Replaces the previous pattern of attaching `$request->apiAuth = [...]` directly
 * to the IncomingRequest object. PHP 8.2+ deprecates dynamic properties on classes
 * that don't declare them.
 *
 * Also stores the resolved end-user identity from the X-Acting-User header
 * (parsed and verified by HmacAuthFilter).
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

    /** Numeric user id from X-Acting-User, or null when absent. */
    public static function actingUserId(): ?int
    {
        $v = self::$auth['acting_user_id'] ?? null;
        return $v === null ? null : (int) $v;
    }

    /** Username from X-Acting-User, or null when absent. */
    public static function actingUsername(): ?string
    {
        $v = self::$auth['acting_username'] ?? null;
        return $v === null ? null : (string) $v;
    }
}
