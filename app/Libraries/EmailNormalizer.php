<?php
namespace App\Libraries;

/**
 * EmailNormalizer — best-effort cleanup for free-text email fields before
 * they are sent to WordPress for an exact lookup.
 *
 * Handles:
 *   * leading / trailing whitespace
 *   * "Display Name <addr@host>" form — extracts the bracketed address
 *   * upper/lower case differences (output is lowercased)
 *
 * Returns null if the result is empty or fails FILTER_VALIDATE_EMAIL, so
 * callers can short-circuit with an "invalid_email" status instead of
 * issuing a doomed WP lookup.
 *
 * NB: this is intentionally lenient — it does NOT try to normalize plus-
 * aliases (alice+wp@) or unicode/IDN domains; WP stores emails verbatim
 * and we want the comparison MySQL does on user_email (utf8mb4_*_ci) to
 * still match.
 */
class EmailNormalizer
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) return null;
        $s = trim($raw);
        if ($s === '') return null;

        // "Display Name <addr@host>" → addr@host
        if (preg_match('/<([^<>\s]+@[^<>\s]+)>/', $s, $m)) {
            $s = $m[1];
        }

        $s = strtolower(trim($s));
        if ($s === '') return null;

        return filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : null;
    }
}
