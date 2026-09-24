<?php
namespace App\Controllers\Api\V1;

use App\Models\ContactModel;

/**
 * Mailchimp sync write-backs (called by the TanStack server with HMAC auth).
 * Applies Mailchimp webhook events to the CRM contacts table when the member
 * can be matched; unmatched events are queued for review by the caller.
 */
class MailchimpController extends BaseApiController
{
    /** Mailchimp region dropdown text -> CRM enum. */
    private const REGION_MAP = [
        'Worldwide (all information)'                  => 'worldwide',
        'North America only'                           => 'North America',
        'China only'                                   => 'China',
        'Asia (including China) only'                  => 'Asia',
        'Europe only'                                  => 'Europe',
        'none - please do not send this information'   => 'none',
    ];

    public function events()
    {
        $payload = $this->request->getJSON(true) ?: [];
        $event   = (string) ($payload['event'] ?? '');
        $email   = strtolower(trim((string) ($payload['email'] ?? '')));
        $dbUser  = (string) ($payload['db_user'] ?? 'mailchimp-sync');
        $model   = new ContactModel();

        $contact = null;
        $contactId = (int) ($payload['contact_id'] ?? 0);
        if ($contactId > 0) {
            $contact = $model->find($contactId);
        }
        if (!$contact && $email !== '') {
            $contact = $model->where('Email', $email)->where('Active', 1)->first();
        }

        switch ($event) {
            case 'unsubscribe':
                if (!$contact) return $this->respond(['ok' => true, 'applied' => false]);
                $model->update($contact['ContactID'], ['EmailPermission' => 0, 'DBuser' => $dbUser]);
                return $this->respond(['ok' => true, 'applied' => true]);

            case 'cleaned':
            case 'spam':
                if (!$contact) return $this->respond(['ok' => true, 'applied' => false]);
                $model->update($contact['ContactID'], ['EmailBounce' => 1, 'DBuser' => $dbUser]);
                return $this->respond(['ok' => true, 'applied' => true]);

            case 'profile':
                if (!$contact) return $this->respond(['ok' => true, 'applied' => false]);
                $patch = $this->profilePatch((array) ($payload['merges'] ?? []));
                if (empty($patch)) return $this->respond(['ok' => true, 'applied' => true, 'changed' => false]);
                $patch['DBuser'] = $dbUser;
                $model->update($contact['ContactID'], $patch);
                return $this->respond(['ok' => true, 'applied' => true]);

            case 'upemail':
                $newEmail = strtolower(trim((string) ($payload['new_email'] ?? '')));
                if (!$contact || $newEmail === '') return $this->respond(['ok' => true, 'applied' => false]);
                $clash = $model->where('Email', $newEmail)->where('ContactID !=', (int) $contact['ContactID'])->first();
                if ($clash) {
                    // Two CRM contacts would share the address — needs review.
                    return $this->respond(['ok' => true, 'applied' => false, 'conflict' => true]);
                }
                $model->update($contact['ContactID'], ['Email' => $newEmail, 'DBuser' => $dbUser]);
                return $this->respond(['ok' => true, 'applied' => true]);

            case 'subscribe':
                // New Mailchimp subscribers always go to the review queue.
                return $this->respond(['ok' => true, 'applied' => false]);

            default:
                return $this->jsonError(400, 'invalid_event', ['event' => $event]);
        }
    }

    /** Mailchimp MERGES payload -> CRM columns. Only non-empty values are applied. */
    private function profilePatch(array $merges): array
    {
        $patch = [];

        $text = [
            'GIVEN'    => 'GivenName',
            'FAMILY'   => 'FamilyName',
            'NICKNAME' => 'Nickname',
            'CN_NAME'  => 'NativeName',
            'TITLE'    => 'Title',
            'COMPANY'  => 'Company',
            'PHONE'    => 'Phone',
            'MOBILE'   => 'Mobile',
        ];
        foreach ($text as $tag => $col) {
            $v = trim((string) ($merges[$tag] ?? ''));
            if ($v !== '') $patch[$col] = $v;
        }

        foreach (['TECH' => 'TechInfo', 'EXPO' => 'ExhibitInfo'] as $tag => $col) {
            $v = trim((string) ($merges[$tag] ?? ''));
            if ($v !== '' && isset(self::REGION_MAP[$v])) $patch[$col] = self::REGION_MAP[$v];
        }

        $lang = trim((string) ($merges['LANGUAGE'] ?? ''));
        if ($lang === 'English') {
            $patch['Language'] = 'English';
        } elseif (str_starts_with($lang, 'Chinese')) {
            $patch['Language'] = 'Chinese';
        }

        $eu = trim((string) ($merges['EUCOUNTRY'] ?? ''));
        if (in_array(strtolower($eu), ['yes', '1', 'true'], true)) {
            $patch['EUCountry'] = 1;
        } elseif (in_array(strtolower($eu), ['no', '0', 'false'], true)) {
            $patch['EUCountry'] = 0;
        }

        return $patch;
    }
}
