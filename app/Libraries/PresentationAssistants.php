<?php
namespace App\Libraries;

use Config\Database;

/** Assistant links are never authors; only unique CRM email matches grant access. */
final class PresentationAssistants
{
    public static function available(): bool
    {
        return Database::connect()->tableExists('presentation_assistants');
    }

    public static function contactForEmail(string $email): ?int
    {
        $rows = Database::connect()->table('contacts')->select('ContactID')
            ->where('LOWER(TRIM(Email))', strtolower(trim($email)))->limit(2)->get()->getResultArray();
        return count($rows) === 1 ? (int) $rows[0]['ContactID'] : null;
    }

    /** Retry pending links after Office creates the CRM contact; no CRM writes here. */
    public static function reconcile(?int $pid = null, ?string $email = null): void
    {
        if (!self::available()) return;
        $db = Database::connect();
        $q = $db->table('presentation_assistants')->where('Status', 'pending_crm');
        if ($pid !== null) $q->where('PresentationID', $pid);
        if ($email !== null) $q->where('Email', strtolower(trim($email)));
        foreach ($q->get()->getResultArray() as $row) {
            $cid = self::contactForEmail($row['Email']);
            if ($cid) $db->table('presentation_assistants')->where('AssistantID', $row['AssistantID'])
                ->where('Status', 'pending_crm')->update(['ContactID' => $cid, 'Status' => 'approved']);
        }
    }

    public static function presentationIds(int $contactId): array
    {
        if ($contactId <= 0 || !self::available()) return [];
        $db = Database::connect();
        $contact = $db->table('contacts')->select('Email')->where('ContactID', $contactId)->get()->getRowArray();
        if (!empty($contact['Email'])) self::reconcile(null, $contact['Email']);
        $rows = $db->table('presentation_assistants a')->select('a.PresentationID')
            ->join('presentations p', 'p.PresentationID = a.PresentationID')
            ->where('a.ContactID', $contactId)->where('a.Status', 'approved')
            ->groupStart()->where('p.Status', 'active')->orWhere('p.Status IS NULL', null, false)->groupEnd()
            ->groupBy('a.PresentationID')->get()->getResultArray();
        return array_map(fn($r) => (int) $r['PresentationID'], $rows);
    }

    public static function rows(int $pid): array
    {
        if (!self::available()) return [];
        self::reconcile($pid);
        return Database::connect()->table('presentation_assistants a')
            ->select('a.*, contacts.Email AS CrmEmail')
            ->join('contacts', 'contacts.ContactID = a.ContactID', 'left')
            ->where('a.PresentationID', $pid)->orderBy('a.AssistantID')->get()->getResultArray();
    }

    /** Pending requests also receive author Cc, but never portal access. */
    public static function emails(int $pid): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($r) => strtolower(trim((string) ($r['CrmEmail'] ?? $r['Email']))), self::rows($pid)
        ), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }
}
