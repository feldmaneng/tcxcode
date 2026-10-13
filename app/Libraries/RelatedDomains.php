<?php
namespace App\Libraries;

use Config\Database;

/**
 * "Related" email domains for a guest list (companyguestlists row).
 *
 * A Full Conference guest is related to the exhibitor when their email domain
 * (or a parent of it, e.g. us.acme.com -> acme.com) is one of these domains:
 *   - email domains of the list's guest-list managers
 *   - website + contact email domains of the linked CRM company and its whole
 *     corporate family (via expodirectory.CompanyGuestListsID -> CompanyID)
 *   - manual extra domains (companyguestlists.RelatedDomains)
 * minus companyguestlists.ExcludedDomains and public mail providers.
 *
 * Computed on the fly; nothing is stored per guest.
 */
class RelatedDomains
{
    /** Free / public mail providers — never count as "related". */
    public const PUBLIC_DOMAINS = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.jp', 'yahoo.com.tw', 'hotmail.com',
        'outlook.com', 'live.com', 'msn.com', 'icloud.com', 'me.com', 'mac.com', 'aol.com',
        'proton.me', 'protonmail.com', 'gmx.com', 'gmx.de', 'mail.com', 'zoho.com', 'yandex.com',
        'qq.com', 'foxmail.com', '163.com', '126.com', 'yeah.net', 'sina.com', 'sina.cn', 'sohu.com',
        'aliyun.com', '139.com', 'naver.com', 'daum.net', 'hanmail.net', 'nate.com', 'kakao.com',
        'hinet.net', 'ms1.hinet.net', 'comcast.net', 'att.net', 'verizon.net', 'sbcglobal.net',
    ];

    /** Lowercase domain from an email or URL ("https://www.acme.com/x" -> "acme.com"). */
    public static function normalizeDomain(string $raw): string
    {
        $s = strtolower(trim($raw));
        if ($s === '') return '';
        if (strpos($s, '@') !== false) $s = substr($s, strrpos($s, '@') + 1);
        $s = preg_replace('#^[a-z]+://#', '', $s) ?? $s;
        $s = preg_split('#[/?\#:\s]#', $s)[0] ?? '';
        if (strpos($s, 'www.') === 0) $s = substr($s, 4);
        $s = trim($s, '.>');
        return preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $s) ? $s : '';
    }

    public static function isPublic(string $domain): bool
    {
        return in_array($domain, self::PUBLIC_DOMAINS, true);
    }

    /** @return string[] */
    public static function parseList(?string $raw): array
    {
        $out = [];
        foreach (preg_split('/[\s,;]+/', (string) $raw) ?: [] as $p) {
            $d = self::normalizeDomain($p);
            if ($d !== '') $out[$d] = true;
        }
        return array_keys($out);
    }

    /**
     * @return array<string,string> domain => reason ("manager email", "CRM company", "manual")
     */
    public static function forList(array $list): array
    {
        $listId = (int) ($list['CompanyID'] ?? 0);
        $found = [];
        $add = function (string $raw, string $reason) use (&$found) {
            $d = self::normalizeDomain($raw);
            if ($d === '' || self::isPublic($d) || isset($found[$d])) return;
            $found[$d] = $reason;
        };

        foreach (self::parseList($list['RelatedDomains'] ?? '') as $d) $found[$d] = 'manual';

        if ($listId > 0) {
            try {
                $reg = Database::connect('registration');
                $userIds = array_map(fn($r) => (int) $r['UserID'], $reg->table('companyguestlists_managers')
                    ->select('UserID')->where('CompanyGuestListsID', $listId)->get()->getResultArray());
                if ($userIds) {
                    $users = Database::connect('control')->table('users')->select('Email')
                        ->whereIn('UserID', $userIds)->get()->getResultArray();
                    foreach ($users as $u) $add((string) ($u['Email'] ?? ''), 'manager email');
                }

                $companyIds = array_values(array_unique(array_filter(array_map(
                    fn($r) => (int) $r['CompanyID'],
                    $reg->table('expodirectory')->select('CompanyID')
                        ->where('CompanyGuestListsID', $listId)->where('DeletedAt', null)
                        ->get()->getResultArray()
                ))));
                $family = [];
                foreach ($companyIds as $cid) {
                    foreach (CompanyFamily::familyIds($cid) as $fid) $family[$fid] = true;
                }
                $family = array_keys($family);
                if ($family) {
                    $db = Database::connect();
                    foreach ($db->table('company')->select('URL')->whereIn('CompanyID', $family)->get()->getResultArray() as $c) {
                        $add((string) ($c['URL'] ?? ''), 'CRM company');
                    }
                    $rows = $db->query(
                        "SELECT DISTINCT LOWER(SUBSTRING_INDEX(TRIM(Email), '@', -1)) AS d
                           FROM contacts WHERE CompanyID IN (" . implode(',', array_map('intval', $family)) . ")
                            AND Email LIKE '%@%'"
                    )->getResultArray();
                    foreach ($rows as $r) $add('x@' . (string) $r['d'], 'CRM company');
                }
            } catch (\Throwable $e) {
                log_message('error', 'RelatedDomains::forList ' . $e->getMessage());
            }
        }

        foreach (self::parseList($list['ExcludedDomains'] ?? '') as $d) unset($found[$d]);
        return $found;
    }

    /** Returns "matches acme.com (CRM company)" when the email is related, else null. */
    public static function match(?string $email, array $domains): ?string
    {
        $d = self::normalizeDomain((string) $email);
        if ($d === '' || !$domains) return null;
        $parts = explode('.', $d);
        while (count($parts) >= 2) {
            $cand = implode('.', $parts);
            if (isset($domains[$cand])) return 'matches ' . $cand . ' (' . $domains[$cand] . ')';
            array_shift($parts);
        }
        return null;
    }
}
