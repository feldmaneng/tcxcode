<?php
namespace App\Libraries;

use App\Models\ContactModel;
use App\Models\UserModel;

/**
 * Shared contact → user resolution / pre-provisioning.
 *
 * Used by:
 *   - AdminUsersController::preprovisionFromContact (explicit admin action)
 *   - CompanyGuestListsController::update           (primary contact becomes
 *     a guest-list manager automatically)
 *
 * Pre-provisioned accounts are WordPress-SSO accounts with no password and
 * no wp_user_id; the row is claimed on the contact's first WP-SSO login.
 */
class ContactUserProvisioner
{
    public function contact(int $contactId): ?array
    {
        try {
            $row = (new ContactModel())->find($contactId);
            return $row ?: null;
        } catch (\Throwable $e) {
            log_message('error', '[ContactUserProvisioner] contact lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /** Username suggestion derived from email local-part, else the name. */
    public function suggestUsername(array $contact): string
    {
        $email = trim((string) ($contact['Email'] ?? ''));
        if ($email !== '' && strpos($email, '@') !== false) {
            $local = strtolower(substr($email, 0, strpos($email, '@')));
            $local = preg_replace('/[^a-z0-9._-]+/', '', $local);
            if ($local !== '') return $local;
        }
        $name = strtolower(trim(($contact['GivenName'] ?? '') . '.' . ($contact['FamilyName'] ?? ''), '.'));
        $name = preg_replace('/[^a-z0-9._-]+/', '', $name);
        return $name !== '' ? $name : ('user' . ($contact['ContactID'] ?? ''));
    }

    /** Appends -2, -3 … until the username is free. */
    public function uniqueUsername(string $base): string
    {
        $userModel = new UserModel();
        $candidate = $base;
        $n = 1;
        while ($userModel->where('LOWER(UserName)', strtolower($candidate))->first()) {
            $n++;
            $candidate = $base . '-' . $n;
            if ($n > 50) { $candidate = $base . '-' . bin2hex(random_bytes(2)); break; }
        }
        return $candidate;
    }

    /** Existing user for this contact, matched by ContactID then email. */
    public function findUserForContact(array $contact): ?array
    {
        $userModel = new UserModel();
        $contactId = (int) ($contact['ContactID'] ?? 0);
        if ($contactId > 0) {
            $row = $userModel->where('ContactID', $contactId)->first();
            if ($row) return $row;
        }
        $email = strtolower(trim((string) ($contact['Email'] ?? '')));
        if ($email !== '') {
            $row = $userModel->where('LOWER(Email)', $email)->first();
            if ($row) return $row;
        }
        return null;
    }

    /**
     * Creates an unclaimed WordPress-SSO user for the contact.
     * @param string[] $modules module codes to grant (may be empty)
     * @return int new UserID
     */
    public function createUserForContact(array $contact, array $modules = []): int
    {
        $contactId = (int) ($contact['ContactID'] ?? 0);
        $email     = trim((string) ($contact['Email'] ?? ''));
        $username  = $this->uniqueUsername($this->suggestUsername($contact));

        $userId = (int) (new UserModel())->insert([
            'UserName'                 => $username,
            'GivenName'                => (string) ($contact['GivenName'] ?? $username),
            'FamilyName'               => (string) ($contact['FamilyName'] ?? ''),
            'Email'                    => $email,
            'PasswordHash'             => null,
            'Active'                   => 1,
            'MustChangePassword'       => 0,
            'auth_provider'            => 'wordpress',
            'wp_user_id'               => null,
            'ProvisionedFromContactID' => $contactId,
            'ContactID'                => $contactId,
        ], true);

        if ($modules !== []) {
            (new \App\Models\UserModuleModel())->setUserModules($userId, $modules);
        }
        return $userId;
    }
}
