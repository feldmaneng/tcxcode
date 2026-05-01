<?php
namespace App\Controllers\Api\V1;

use App\Models\PresentationModel;
use App\Models\AuthorModel;
use Config\Database;

class PresentationsController extends BaseApiController
{
    private const FIELD_MAP = [
        'id'                   => 'PresentationID',
        'event'                => 'Event',
        'year'                 => 'Year',
        'session'              => 'Session',
        'presentation_number'  => 'PresentationNumber',
        'title'                => 'Title',
        'title_chinese'        => 'TitleChinese',
        'title_korean'         => 'TitleKorean',
        'wrangler'             => 'Wrangler',
        'topic'                => 'Topic',
        'award'                => 'Award',
        'url'                  => 'URL',
        'base_file_name'       => 'BaseFileName',
        'pdf_lock_code'        => 'PDFLockCode',
        'video_id'             => 'VideoID',
        'abstract_number'      => 'AbstractNumber',
        'early_bird'           => 'EarlyBird',
        'author_discount_code' => 'AuthorDiscountCode',
        'wrangler_id'          => 'WranglerID',
        'abstract_english'     => 'AbstractEnglish',
        'abstract_chinese'     => 'AbstractChinese',
        'abstract_korean'      => 'AbstractKorean',
        'bio_english'          => 'BioEnglish',
        'bio_chinese'          => 'BioChinese',
        'bio_korean'           => 'BioKorean',
    ];

    private const READONLY_API_FIELDS = ['id'];
    private const FILTERABLE = ['event', 'year', 'session', 'topic', 'award', 'wrangler_id'];
    private const SORTABLE   = ['id', 'year', 'event', 'session', 'presentation_number', 'title'];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        return $out;
    }

    private function apiToDb(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if ($k === 'authors') continue;
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

    private function attachAuthors(array &$row): void
    {
        $rows = (new AuthorModel())->builder()
            ->where('PresentationID', (int) $row['id'])
            ->orderBy('AuthorNumber', 'ASC')
            ->orderBy('AuthorID', 'ASC')
            ->get()->getResultArray();
        $row['authors'] = array_map(fn($r) => AuthorsController::dbToApi($r), $rows);
    }

    public function index()
    {
        $req     = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = max(1, min(100, (int) ($req->getGet('per_page') ?: 25)));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: '-year');

        $builder = (new PresentationModel())->builder();
        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('Title', $q)
                ->orLike('AbstractNumber', $q)
                ->orLike('BaseFileName', $q)
                ->orLike('Topic', $q);
            if (ctype_digit($q)) {
                $builder->orWhere('PresentationID', (int) $q);
            }
            $builder->groupEnd();
        }

        foreach (explode(',', $sort) as $s) {
            $s = trim($s);
            if ($s === '') continue;
            $dir = 'ASC';
            if (str_starts_with($s, '-')) { $dir = 'DESC'; $s = substr($s, 1); }
            if (in_array($s, self::SORTABLE, true)) {
                $builder->orderBy(self::FIELD_MAP[$s], $dir);
            }
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $api = $this->dbToApi($r);
            // Add lightweight author count for list view
            $api['author_count'] = (int) (new AuthorModel())->builder()
                ->where('PresentationID', (int) $api['id'])->countAllResults();
            $data[] = $api;
        }

        return $this->response->setJSON([
            'data' => $data,
            'pagination' => [
                'page' => $page, 'per_page' => $perPage,
                'total' => $total, 'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function show($id = null)
    {
        $row = (new PresentationModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        $api = $this->dbToApi($row);
        $this->attachAuthors($api);
        return $this->response->setJSON(['data' => $api]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        $model = new PresentationModel();
        $id = $model->insert($dbRow, true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());
        if (isset($payload['authors']) && is_array($payload['authors'])) {
            $this->replaceAuthors((int) $id, $payload['authors']);
        }
        return $this->show((int) $id)->setStatusCode(201);
    }

    public function update($id = null)
    {
        $model = new PresentationModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        $payload = $this->request->getJSON(true) ?? [];
        $dbRow = $this->apiToDb($payload);
        $hasAuthors = array_key_exists('authors', $payload) && is_array($payload['authors']);
        if (empty($dbRow) && !$hasAuthors) return $this->jsonError(400, 'no_updatable_fields');
        if (!empty($dbRow)) {
            if (!$model->update((int) $id, $dbRow)) {
                return $this->jsonError(500, 'update_failed', $model->errors());
            }
        }
        if ($hasAuthors) {
            $this->replaceAuthors((int) $id, $payload['authors']);
        }
        return $this->show((int) $id);
    }

    public function delete($id = null)
    {
        $model = new PresentationModel();
        if (!$model->find((int) $id)) return $this->jsonError(404, 'not_found');
        $db = Database::connect();
        $db->table('authors')->where('PresentationID', (int) $id)->delete();
        if (!$model->delete((int) $id)) return $this->jsonError(500, 'delete_failed', $model->errors());
        return $this->response->setJSON(['data' => ['id' => (int) $id, 'deleted' => true]]);
    }

    /**
     * Replace the author set for a presentation. Each entry in $authors should be
     * { contact_id, presenter?, author_number? }. Snapshot of name + company is
     * captured from the contacts table at save time. Existing authors are wiped
     * and re-inserted (simpler + matches the "snapshot at add time" semantics).
     */
    private function replaceAuthors(int $presentationId, array $authors): void
    {
        $db = Database::connect();
        $db->transStart();
        $db->table('authors')->where('PresentationID', $presentationId)->delete();

        $idx = 1;
        foreach ($authors as $a) {
            if (!is_array($a)) continue;
            $contactId = isset($a['contact_id']) ? (int) $a['contact_id'] : 0;
            if ($contactId <= 0) { $idx++; continue; }
            $row = [
                'PresentationID' => $presentationId,
                'ContactID'      => $contactId,
                'AuthorNumber'   => isset($a['author_number']) ? (int) $a['author_number'] : $idx,
                'Presenter'      => !empty($a['presenter']) ? 1 : 0,
            ];
            AuthorsController::snapshotFromContact($row, $contactId);
            $db->table('authors')->insert($row);
            $idx++;
        }
        $db->transComplete();
    }
}
