<?php
namespace App\Libraries;

use App\Models\CompanyGuestListsManagerModel;

/**
 * Adds a contact as a guest-list manager, pre-provisioning a user account when
 * the contact has none.
 *
 * Used by:
 *   - CompanyGuestListsController (primary contact becomes a manager)
 *   - ExpoDirectoryController     (exhibitor coordinators become managers of
 *                                  the linked guest list)
 *
 * Never throws: guest-list manager sync must not fail the caller's save.
 */
class GuestListManagerSync
{
    public const MAX_MANAGERS = 4;

    /**
     * @return string one of: added | created_and_added | already | limit_reached | failed | no_email
     */
    public function ensureContactIsManager(int $companyGuestListsId, array $contact, ?int $actorId = null): string
    {
        try {
            if (trim((string) ($contact['Email'] ?? '')) === '') return 'no_email';

            $provisioner = new ContactUserProvisioner();
            $user   = $provisioner->findUserForContact($contact);
            $status = 'added';
            if ($user) {
                $userId = (int) $user['UserID'];
            } else {
                $userId = $provisioner->createUserForContact($contact, []);
                $status = 'created_and_added';
            }
            if ($userId <= 0) return 'failed';

            $mgrs    = new CompanyGuestListsManagerModel();
            $current = $mgrs->userIdsForCompany($companyGuestListsId);
            if (in_array($userId, $current, true)) return 'already';
            if (count($current) >= self::MAX_MANAGERS) return 'limit_reached';

            $mgrs->insert([
                'CompanyGuestListsID' => $companyGuestListsId,
                'UserID'              => $userId,
                'AddedBy'             => $actorId,
            ]);

            try {
                (new \App\Models\AdminAuditLogModel())->log(
                    (int) $actorId,
                    'guestlist.manager_synced',
                    'companyguestlists',
                    (string) $companyGuestListsId,
                    ['user_id' => $userId, 'contact_id' => (int) ($contact['ContactID'] ?? 0), 'created' => $status === 'created_and_added'],
                    null
                );
            } catch (\Throwable $e) {}

            return $status;
        } catch (\Throwable $e) {
            log_message('error', '[GuestListManagerSync] failed: ' . $e->getMessage());
            return 'failed';
        }
    }

    /**
     * Syncs a set of contact ids as managers of a guest list.
     * @param int[] $contactIds
     * @return array<int,string> status keyed by contact id
     */
    public function syncContacts(int $companyGuestListsId, array $contactIds, ?int $actorId = null): array
    {
        $out  = [];
        $prov = new ContactUserProvisioner();
        foreach ($contactIds as $cid) {
            $cid = (int) $cid;
            if ($cid <= 0) continue;
            $contact = $prov->contact($cid);
            if (!$contact) { $out[$cid] = 'failed'; continue; }
            $out[$cid] = $this->ensureContactIsManager($companyGuestListsId, $contact, $actorId);
        }
        return $out;
    }
}
