<?php
namespace App\Libraries;

class HmacVerifier
{
    /**
     * Verify HMAC signature on an incoming request.
     *
     * Canonical string (5 lines):
     *   METHOD\nPATH\nTIMESTAMP\nSHA256_HEX(body)\nX-Acting-User-value
     *
     * The X-Acting-User line is empty when the request has no end-user context
     * (e.g. service-to-service calls during login or refresh). It is included
     * unconditionally so the signature covers it whenever it IS present, which
     * makes the header tamper-proof.
     */
    public static function verify(
        string $method,
        string $path,
        string $timestamp,
        string $body,
        string $providedSig,
        string $secret,
        int $maxSkewSeconds = 300,
        string $actingUserHeader = ''
    ): array {
        $now = time();
        $ts  = (int) $timestamp;
        if ($ts <= 0 || abs($now - $ts) > $maxSkewSeconds) {
            return [false, 'timestamp_skew'];
        }
        $bodyHash  = hash('sha256', $body);
        $canonical = strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . $bodyHash . "\n" . $actingUserHeader;
        $expected  = hash_hmac('sha256', $canonical, $secret);
        if (!hash_equals($expected, strtolower($providedSig))) {
            return [false, 'bad_signature'];
        }
        return [true, null];
    }
}
