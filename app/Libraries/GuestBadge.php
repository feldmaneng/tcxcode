<?php
namespace App\Libraries;

/**
 * Fetches a single guest's badge PDF from the existing badge generator
 * (www.testconx.org/forms.php/Guest/TestConXsingle/{GuestID}).
 *
 * The generator is called server-to-server; the browser never sees its URL.
 * Configure in .env:
 *   badge.sourceUrl   = https://www.testconx.org/forms.php/Guest/TestConXsingle/
 *   badge.sharedToken = <long random string>   (sent as X-Badge-Token; the
 *                       optional guard in TestConXsingle checks it)
 */
final class GuestBadge
{
    /** @return array{ok:bool,pdf?:string,error?:string} */
    public static function fetch(int $guestId): array
    {
        $base  = rtrim((string) env('badge.sourceUrl', 'https://www.testconx.org/forms.php/Guest/TestConXsingle/'), '/') . '/';
        $token = (string) env('badge.sharedToken', '');

        $headers = ['Accept' => 'application/pdf'];
        if ($token !== '') $headers['X-Badge-Token'] = $token;

        try {
            $res = \Config\Services::curlrequest(['timeout' => 30, 'http_errors' => false])
                ->get($base . $guestId, ['headers' => $headers]);
        } catch (\Throwable $e) {
            log_message('error', '[GuestBadge] fetch failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'badge_unavailable'];
        }

        $body = (string) $res->getBody();
        $type = strtolower($res->getHeaderLine('Content-Type'));
        if ($res->getStatusCode() !== 200 || (strpos($type, 'pdf') === false && strncmp($body, '%PDF', 4) !== 0)) {
            log_message('error', '[GuestBadge] unexpected response ' . $res->getStatusCode() . ' ' . $type);
            return ['ok' => false, 'error' => 'badge_unavailable'];
        }
        return ['ok' => true, 'pdf' => $body];
    }
}
