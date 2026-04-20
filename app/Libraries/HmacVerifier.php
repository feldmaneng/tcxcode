<?php
namespace App\Libraries;

class HmacVerifier
{
    /**
     * Verify HMAC signature on an incoming request.
     * canonical = METHOD\nPATH\nTIMESTAMP\nSHA256_HEX(body)
     */
    public static function verify(string $method, string $path, string $timestamp, string $body, string $providedSig, string $secret, int $maxSkewSeconds = 300): array
    {
        $now = time();
        $ts  = (int) $timestamp;
        if ($ts <= 0 || abs($now - $ts) > $maxSkewSeconds) {
            return [false, 'timestamp_skew'];
        }
        $bodyHash  = hash('sha256', $body);
        $canonical = strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . $bodyHash;
        $expected  = hash_hmac('sha256', $canonical, $secret);
        if (!hash_equals($expected, strtolower($providedSig))) {
            return [false, 'bad_signature'];
        }
        return [true, null];
    }
}
