<?php
namespace App\Controllers\Api\V1;

use App\Models\ContactModel;

class ContactsController extends BaseApiController
{
    /**
     * Maps API snake_case <-> DB PascalCase. Excludes d2000..d2009 entirely.
     */
    private const FIELD_MAP = [
        'id'                  => 'ContactID',
        'stamp'               => 'Stamp',
        'db_user'             => 'DBuser',
        'email'               => 'Email',
        'given_name'          => 'GivenName',
        'family_name'         => 'FamilyName',
        'nickname'            => 'Nickname',
        'address_as'          => 'AddressAs',
        'active'              => 'Active',
        'native_language'     => 'NativeLanguage',
        'native_name'         => 'NativeName',
        'expo_mailing'        => 'Expo_mailing',
        'tech_mailing'        => 'Tech_mailing',
        'china_mailing'       => 'China_mailing',
        'correspondence_type' => 'CorrespondenceType',
        'email_only'          => 'Email_only',
        'record_type'         => 'Record_type',
        'abbr'                => 'Abbr',
        'title'               => 'Title',
        'company_id'          => 'CompanyID',
        'parent_company_id'   => 'ParentCompanyID',
        'company'             => 'Company',
        'cn_company'          => 'CN_Company',
        'address1'            => 'Address1',
        'address2'            => 'Address2',
        'city'                => 'City',
        'state'               => 'State',
        'country'             => 'Country',
        'p_code'              => 'PCode',
        'cn_address'          => 'CN_Address',
        'website'             => 'Website',
        'phone'               => 'Phone',
        'fax'                 => 'Fax',
        'mobile'              => 'Mobile',
        'source'              => 'Source',
        'origin'              => 'Origin',
        'solicitation'        => 'Solicitation',
        'linkedin_email'      => 'LinkedInEmail',
        'linkedin_url'        => 'LinkedInURL',
        'linkedin_group'      => 'LinkedInGroup',
        'wordpress_id'        => 'WordPressID',
        'notes'               => 'Notes',
        'added'               => 'Added',
        'eu_country'          => 'EUCountry',
        'tech_info'           => 'TechInfo',
        'exhibit_info'        => 'ExhibitInfo',
        'language'            => 'Language',
        'email_permission'    => 'EmailPermission',
        'postal_permission'   => 'PostalPermission',
        'app_permission'      => 'AppPermission',
        'email_bounce'        => 'EmailBounce',
        'wechat_id'           => 'WeChatID',
    ];

    private const READONLY_API_FIELDS = ['id','stamp','added'];

    /** Whitelisted filter columns (API names) */
    private const FILTERABLE = [
        'active','country','state','city','record_type','language',
        'tech_info','exhibit_info','company_id','email_permission',
        'postal_permission','email_bounce','eu_country',
    ];

    /** Whitelisted sort columns (API names) */
    private const SORTABLE = [
        'id','family_name','given_name','company','country','city',
        'added','stamp','email',
    ];

    private function dbToApi(array $row): array
    {
        $out = [];
        foreach (self::FIELD_MAP as $api => $db) {
            if (array_key_exists($db, $row)) $out[$api] = $row[$db];
        }
        return $out;
    }

    private function apiToDb(array $payload, bool $isUpdate = false): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (in_array($k, self::READONLY_API_FIELDS, true)) continue;
            if (!isset(self::FIELD_MAP[$k])) continue;
            $out[self::FIELD_MAP[$k]] = $v;
        }
        return $out;
    }

	private function write_archive ($id = null, $delete = FALSE) {
		// Grab the existing record
        $old_row = (new ContactModel())->find((int) $id);
        if (!$old_row) return $this->jsonError(404, 'Old row not_found');

		// We've also assumed only one record got returend since primary_key is unique
		//If we have more than 1 row, for some reason, only the first row will be written to the archive
		$archive_row = $old_row;
		
		//Save the ContactID since the ForeignKey relationship will set the ContactID to NULL when the 
		//record is deleted from Contacts
		$archive_row['OriginalContactID'] = $old_row['ContactID'];
		
		// Modify the notes field to indicate who deleted this record
		// Unfortunately we don't have the user info handy $this->determine_user()
		if ( $delete ) {
			$archive_row['Notes'] = "DELETED " . date("Y-m-d h:i:sa") . " by API ". $archive_row['Notes'];
		}
	
		$ok = $db->table('contacts_archive')->insert($archive_row); 
		if (!$ok) return $this->jsonError(500, 'write_archive_failed', $db->errors());
		return;
	
	}
	
    /** GET /api/v1/contacts */
    public function index()
    {
        $req = $this->request;
        $page    = max(1, (int) $req->getGet('page') ?: 1);
        $perPage = (int) ($req->getGet('per_page') ?: 25);
        $perPage = max(1, min(100, $perPage));
        $q       = trim((string) $req->getGet('q'));
        $sort    = (string) ($req->getGet('sort') ?: '-added');

        $builder = (new ContactModel())->builder();

        // Filters (whitelisted)
        foreach (self::FILTERABLE as $apiCol) {
            $val = $req->getGet($apiCol);
            if ($val === null || $val === '') continue;
            $builder->where(self::FIELD_MAP[$apiCol], $val);
        }

        // Free-text search
        if ($q !== '') {
            $builder->groupStart()
                ->like('GivenName', $q)
                ->orLike('FamilyName', $q)
                ->orLike('Company', $q)
                ->orLike('Email', $q)
                ->groupEnd();
        }

        // Sort: comma-separated, prefix - for desc
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

        return $this->response->setJSON([
            'data' => array_map(fn($r) => $this->dbToApi($r), $rows),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /** GET /api/v1/contacts/{id} */
    public function show($id = null)
    {
        $row = (new ContactModel())->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        return $this->response->setJSON(['data' => $this->dbToApi($row)]);
    }

    /** POST /api/v1/contacts */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $rules = $this->validationRules(false);
        if (!$this->validateData($payload, $rules)) {
            return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());
        }
        $dbRow = $this->apiToDb($payload);
        if (!array_key_exists('Active', $dbRow)) $dbRow['Active'] = 1;
        $model = new ContactModel();
        $id = $model->insert($dbRow, true);
        if (!$id) return $this->jsonError(500, 'insert_failed', $model->errors());
        $created = $model->find((int) $id);
        return $this->response->setStatusCode(201)->setJSON(['data' => $this->dbToApi($created)]);
    }

    /** PUT /api/v1/contacts/{id} */
    public function update($id = null)
    {
        $model = new ContactModel();
        $existing = $model->find((int) $id);
        if (!$existing) return $this->jsonError(404, 'not_found');

        $payload = $this->request->getJSON(true) ?? [];
        $rules = $this->validationRules(true, (int) $id);
        if (!$this->validateData($payload, $rules)) {
            return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());
        }
        $dbRow = $this->apiToDb($payload, true);
        if (empty($dbRow)) return $this->jsonError(400, 'no_updatable_fields');

		// Archive old copy
		$ok = $this->write_archive ($id);
			
		$ok = $model->update((int) $id, $dbRow);
        if (!$ok) return $this->jsonError(500, 'update_failed', $model->errors());
        return $this->response->setJSON(['data' => $this->dbToApi($model->find((int) $id))]);
    }

    /** DELETE /api/v1/contacts/{id} — hard delete */
    public function delete($id = null)
    {
        $model = new ContactModel();
        $row = $model->find((int) $id);
        if (!$row) return $this->jsonError(404, 'not_found');
        
		// Archive old copy
		$ok = $this->write_archive ($id, TRUE);
		
        // $model->update((int) $id, ['Active' => 0, 'EmailPermission' => 0]);
        $ok = $model->delete((int) $id);
        if (!$ok) return $this->jsonError(500, 'delete_failed', $model->errors());
        
        // maybe fix the return 
        return $this->response->setStatusCode(200)->setJSON(['data' => ['id' => (int) $id, 'active' => 0, 'soft_deleted' => true]]);
    }

    /**
     * Validation rules. CREATE requires email; UPDATE only validates submitted fields.
     */
    private function validationRules(bool $isUpdate, ?int $idForUnique = null): array
    {
        $emailUnique = 'is_unique[contacts.Email,ContactID,' . ($idForUnique ?? '{id}') . ']';
        $base = [
            'email'             => ($isUpdate ? 'permit_empty' : 'required') . '|valid_email|max_length[100]|' . $emailUnique,
            'given_name'        => 'permit_empty|string|max_length[50]',
            'family_name'       => 'permit_empty|string|max_length[50]',
            'nickname'          => 'permit_empty|string|max_length[50]',
            'address_as'        => 'permit_empty|string|max_length[50]',
            'active'            => 'permit_empty|in_list[0,1]',
            'native_language'   => 'permit_empty|in_list[Chinese,Japanese,Korean,Other]',
            'native_name'       => 'permit_empty|string|max_length[8]',
            'correspondence_type' => 'permit_empty|in_list[Both,Postal,Email]',
            'email_only'        => 'permit_empty|in_list[0,1]',
            'record_type'       => 'permit_empty|string|max_length[50]',
            'abbr'              => 'permit_empty|string|max_length[50]',
            'title'             => 'permit_empty|string|max_length[50]',
            'company_id'        => 'permit_empty|is_natural',
            'parent_company_id' => 'permit_empty|is_natural',
            'company'           => 'permit_empty|string|max_length[100]',
            'cn_company'        => 'permit_empty|string|max_length[50]',
            'address1'          => 'permit_empty|string|max_length[255]',
            'address2'          => 'permit_empty|string|max_length[50]',
            'city'              => 'permit_empty|string|max_length[50]',
            'state'             => 'permit_empty|string|max_length[20]',
            'country'           => 'permit_empty|string|max_length[50]',
            'p_code'            => 'permit_empty|string|max_length[20]',
            'cn_address'        => 'permit_empty|string|max_length[255]',
            'website'           => 'permit_empty|valid_url_strict|max_length[200]',
            'phone'             => 'permit_empty|string|max_length[30]',
            'fax'               => 'permit_empty|string|max_length[30]',
            'mobile'            => 'permit_empty|string|max_length[30]',
            'source'            => 'permit_empty|string|max_length[50]',
            'origin'            => 'permit_empty|string|max_length[100]',
            'solicitation'      => 'permit_empty|in_list[0,1]',
            'linkedin_email'    => 'permit_empty|valid_email|max_length[50]',
            'linkedin_url'      => 'permit_empty|valid_url_strict|max_length[200]',
            'linkedin_group'    => 'permit_empty|in_list[0,1]',
            'wordpress_id'      => 'permit_empty|is_natural',
            'notes'             => 'permit_empty|string',
            'eu_country'        => 'permit_empty|in_list[0,1]',
            'tech_info'         => 'permit_empty|in_list[worldwide,North America,China,Asia,Europe,none]',
            'exhibit_info'      => 'permit_empty|in_list[worldwide,North America,China,Asia,Europe,none]',
            'language'          => 'permit_empty|in_list[English,Chinese,Korean]',
            'email_permission'  => 'permit_empty|in_list[0,1]',
            'postal_permission' => 'permit_empty|in_list[0,1]',
            'app_permission'    => 'permit_empty|in_list[0,1]',
            'email_bounce'      => 'permit_empty|in_list[0,1]',
            'wechat_id'         => 'permit_empty|string|max_length[20]',
        ];
        return $base;
    }
}
