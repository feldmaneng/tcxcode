<?php
namespace App\Libraries;

use Config\Database;

/**
 * Prefer the CRM contact email (contacts.Email via users.ContactID) over the
 * login account email. Falls back to users.Email when no contact email exists.
 */
class CrmEmail
{
    /** @param array<int,array> $users rows containing ContactID + Email; Email is rewritten in place. */
    public static function apply(array $users): array
    {
        $cids = [];
        foreach ($users as $u) {
            $c = (int) ($u['ContactID'] ?? 0);
            if ($c > 0) $cids[$c] = true;
        }
        if (!$cids) return $users;
        try {
            $rows = Database::connect()->table('contacts')
                ->select('ContactID, Email')
                ->whereIn('ContactID', array_keys($cids))
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', '[CrmEmail] contact lookup failed: ' . $e->getMessage());
            return $users;
        }
        $map = [];
        foreach ($rows as $r) {
            $e = trim((string) ($r['Email'] ?? ''));
            if ($e !== '') $map[(int) $r['ContactID']] = $e;
        }
        foreach ($users as $k => $u) {
            $c = (int) ($u['ContactID'] ?? 0);
            if ($c > 0 && isset($map[$c])) {
                $users[$k]['LoginEmail'] = $u['Email'] ?? null;
                $users[$k]['Email'] = $map[$c];
            }
        }
        return $users;
    }

    public static function applyOne(?array $user): ?array
    {
        if (!$user) return $user;
        return self::apply([$user])[0];
    }

    /** Find the active user whose CRM email OR login email matches. */
    public static function findActiveUserByEmail(string $email, string $select): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') return null;
        $dbC = Database::connect('control');
        $u = $dbC->table('users')->select($select)
            ->where('LOWER(Email)', $email)->where('Active', 1)
            ->get()->getRowArray();
        if ($u) return $u;
        try {
            $cids = array_map(fn($r) => (int) $r['ContactID'], Database::connect()->table('contacts')
                ->select('ContactID')->where('LOWER(Email)', $email)->get()->getResultArray());
        } catch (\Throwable $e) {
            return null;
        }
        if (!$cids) return null;
        return $dbC->table('users')->select($select)
            ->whereIn('ContactID', $cids)->where('Active', 1)
            ->orderBy('UserID', 'ASC')->get()->getRowArray() ?: null;
    }
}
