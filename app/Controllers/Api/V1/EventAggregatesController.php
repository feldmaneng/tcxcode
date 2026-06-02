<?php
namespace App\Controllers\Api\V1;

use App\Models\EventModel;
use Config\Database;

/**
 * Pre-aggregated attendance views for the Segmentation module.
 *
 * Returns one row per company (or per location triple), already grouped and
 * counted in SQL, so the TanStack server can build a universe with a single
 * HTTP call instead of fanning out hundreds of /contacts/{id} + /companies/{id}
 * lookups (which trip the per-IP throttle).
 *
 * All endpoints follow the same response envelope as the rest of /api/v1.
 *
 *   GET /api/v1/events/{id}/company-attendance
 *   GET /api/v1/events/{id}/location-attendance
 */
class EventAggregatesController extends BaseApiController
{
    /** Classify an attendance.Type string into one of our bucket codes. */
    private function classifyType(?string $type): string
    {
        $s = strtolower((string) $type);
        if ($s === '') return 'other';
        if (str_contains($s, 'expo')) return 'expo_only';
        if (str_contains($s, 'exhibit')) return 'exhibitor';
        if (
            str_contains($s, 'professional')
            || str_contains($s, 'attendee')
            || str_contains($s, 'delegate')
            || $s === 'regular'
        ) return 'professional';
        return 'other';
    }

    private function emptyCounts(): array
    {
        return [
            'attendees_total'        => 0,
            'attendees_professional' => 0,
            'attendees_exhibitor'    => 0,
            'attendees_expo_only'    => 0,
        ];
    }

    private function addBucket(array &$counts, string $type, int $n): void
    {
        $counts['attendees_total'] += $n;
        $k = $this->classifyType($type);
        if ($k === 'professional')      $counts['attendees_professional'] += $n;
        elseif ($k === 'exhibitor')     $counts['attendees_exhibitor']    += $n;
        elseif ($k === 'expo_only')     $counts['attendees_expo_only']    += $n;
        // 'other' contributes to total only.
    }

    /** Resolve event_id → (Name, Year) or return null. */
    private function resolveEvent(int $eventId): ?array
    {
        $ev = (new EventModel())->select('Name, Year')->find($eventId);
        if (!$ev || empty($ev['Name']) || empty($ev['Year'])) return null;
        return ['name' => (string) $ev['Name'], 'year' => (int) $ev['Year']];
    }

    /**
     * GET /api/v1/events/{id}/company-attendance
     *
     * Joins attendance → contacts → company on the CI4 side. Returns one row
     * per CompanyID with pre-summed buckets. Contacts without a CompanyID are
     * skipped (universe grouping is strictly by company record id).
     */
    public function companyAttendance(int $eventId)
    {
        $ev = $this->resolveEvent($eventId);
        if (!$ev) return $this->jsonError(404, 'event_not_found');

        $db = Database::connect();
        $rows = $db->table('attendance a')
            ->select('c.CompanyID AS company_id, c.Name AS company_name, a.Type AS type, COUNT(DISTINCT a.ContactID) AS n', false)
            ->join('contacts ct', 'ct.ContactID = a.ContactID', 'inner')
            ->join('company c',   'c.CompanyID  = ct.CompanyID', 'inner')
            ->where('a.Event', $ev['name'])
            ->where('a.Year',  $ev['year'])
            ->where('a.Show',  '1')
            ->where('ct.CompanyID IS NOT NULL', null, false)
            ->where('ct.CompanyID >', 0)
            ->groupBy(['c.CompanyID', 'c.Name', 'a.Type'])
            ->get()
            ->getResultArray();

        $buckets = []; // company_id => [company_name, counts]
        foreach ($rows as $r) {
            $cid = (int) $r['company_id'];
            if ($cid <= 0) continue;
            if (!isset($buckets[$cid])) {
                $buckets[$cid] = [
                    'company_id'   => $cid,
                    'company_name' => (string) $r['company_name'],
                    'counts'       => $this->emptyCounts(),
                ];
            }
            $this->addBucket($buckets[$cid]['counts'], (string) $r['type'], (int) $r['n']);
        }

        $data = array_values($buckets);
        usort($data, fn($a, $b) => strcasecmp($a['company_name'], $b['company_name']));

        return $this->response->setJSON(['data' => $data]);
    }

    /**
     * GET /api/v1/events/{id}/location-attendance
     *
     * Groups by (City, State, Country). Dedup-by-contact happens via
     * COUNT(DISTINCT a.ContactID). Rows with all three location fields empty
     * are skipped.
     */
    public function locationAttendance(int $eventId)
    {
        $ev = $this->resolveEvent($eventId);
        if (!$ev) return $this->jsonError(404, 'event_not_found');

        $db = Database::connect();
        $rows = $db->table('attendance a')
            ->select("TRIM(COALESCE(ct.City,''))    AS city,
                      TRIM(COALESCE(ct.State,''))   AS state,
                      TRIM(COALESCE(ct.Country,'')) AS country,
                      a.Type AS type,
                      COUNT(DISTINCT a.ContactID) AS n", false)
            ->join('contacts ct', 'ct.ContactID = a.ContactID', 'inner')
            ->where('a.Event', $ev['name'])
            ->where('a.Year',  $ev['year'])
            ->where('a.Show',  '1')
            ->groupBy(['city', 'state', 'country', 'a.Type'])
            ->get()
            ->getResultArray();

        $buckets = []; // "city|state|country" => bucket
        foreach ($rows as $r) {
            $city    = (string) ($r['city']    ?? '');
            $state   = (string) ($r['state']   ?? '');
            $country = (string) ($r['country'] ?? '');
            if ($city === '' && $state === '' && $country === '') continue;
            $key = $city . '|' . $state . '|' . $country;
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'city'    => $city,
                    'state'   => $state,
                    'country' => $country,
                    'label'   => sprintf('%s | %s | %s', $city !== '' ? $city : '—',
                                                          $state !== '' ? $state : '—',
                                                          $country !== '' ? $country : '—'),
                    'counts'  => $this->emptyCounts(),
                ];
            }
            $this->addBucket($buckets[$key]['counts'], (string) $r['type'], (int) $r['n']);
        }

        $data = array_values($buckets);
        usort($data, fn($a, $b) => strcasecmp($a['label'], $b['label']));

        return $this->response->setJSON(['data' => $data]);
    }
}
