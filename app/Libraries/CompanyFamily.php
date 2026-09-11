<?php
namespace App\Libraries;

use Config\Database;

/**
 * Company "corporate family" resolution on the conference DB `company` table.
 *
 * familyIds(rootCompanyId) = every company that eventually rolls up to the
 * same ultimate parent as $rootCompanyId: walk ParentID upward to the top,
 * then BFS the whole subtree beneath that top. All lookups are level-batched
 * (one query per tree level), never per company.
 */
class CompanyFamily
{
    private const MAX_DEPTH = 10;

    /** Walk ParentID upward; returns the topmost ancestor (or self). Cycle-safe. */
    public static function ultimateParentId(int $companyId): int
    {
        $db = Database::connect();
        $current = $companyId;
        $seen = [$companyId => true];
        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            $row = $db->table('company')->select('ParentID')
                ->where('CompanyID', $current)->get()->getRowArray();
            if (!$row) break; // company missing — stop
            $parent = (int) ($row['ParentID'] ?? 0);
            if ($parent <= 0 || isset($seen[$parent])) break;
            $seen[$parent] = true;
            $current = $parent;
        }
        return $current;
    }

    /**
     * All CompanyIDs in the corporate family of $companyId (ultimate parent +
     * its entire descendant subtree). Always includes $companyId when it exists.
     *
     * @return int[]
     */
    public static function familyIds(int $companyId): array
    {
        if ($companyId <= 0) return [];
        $db = Database::connect();
        $root = self::ultimateParentId($companyId);
        $all = [$root => true];
        $frontier = [$root];
        for ($depth = 0; $depth < self::MAX_DEPTH && !empty($frontier); $depth++) {
            $rows = $db->table('company')->select('CompanyID')
                ->whereIn('ParentID', $frontier)->get()->getResultArray();
            $next = [];
            foreach ($rows as $r) {
                $cid = (int) $r['CompanyID'];
                if (isset($all[$cid])) continue;
                $all[$cid] = true;
                $next[] = $cid;
            }
            $frontier = $next;
        }
        return array_map('intval', array_keys($all));
    }
}
