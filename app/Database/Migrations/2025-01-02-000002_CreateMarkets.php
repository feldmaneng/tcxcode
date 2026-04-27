<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Adapts the existing legacy `markets` table to support hierarchical tags
 * with materialized paths.
 *
 * Existing schema (preserved data):
 *   ID int unsigned PK auto_increment
 *   ParentID int unsigned NULL
 *   Market varchar(256)
 *
 * Target schema (after migration):
 *   MarketID int unsigned PK auto_increment   (renamed from ID)
 *   ParentID int unsigned NULL
 *   Name varchar(80)                          (renamed from Market, narrowed)
 *   Slug varchar(80)
 *   Path varchar(500)
 *   Depth tinyint unsigned default 0
 *   Active tinyint(1) default 1
 *   Sort int default 0
 *   Added datetime NULL
 *   Stamp datetime NULL
 *
 * Foreign keys that point at markets.ID elsewhere keep working because the
 * column is renamed (values preserved), not dropped/recreated.
 */
class CreateMarkets extends Migration
{
    public function up()
    {
        $forge = $this->forge;
        $db    = Database::connect();

        // 1) Rename + retype existing columns. CHANGE COLUMN preserves data.
        //    Note: existing Market is varchar(256); we narrow to 80. If any
        //    legacy row exceeds 80 chars this will truncate — acceptable for
        //    tag names. If you have longer values, raise this limit.
        $forge->modifyColumn('markets', [
            'ID' => [
                'name'           => 'MarketID',
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Market' => [
                'name'       => 'Name',
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
        ]);

        // 2) Add the new columns required by the tag tree.
        $forge->addColumn('markets', [
            'Slug'   => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'Name'],
            'Path'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'Slug'],
            'Depth'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0, 'after' => 'Path'],
            'Active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'Depth'],
            'Sort'   => ['type' => 'INT', 'default' => 0, 'after' => 'Active'],
            'Added'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'Sort'],
            'Stamp'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'Added'],
        ]);

        // 3) Backfill Slug, then Path/Depth based on the tree.
        //    Slug = lowercased Name with non-alnum collapsed to '-'.
        //    Done in PHP (not SQL) to keep behavior identical to MarketTree::slugify.
        $rows = $db->table('markets')->select('MarketID, ParentID, Name')->get()->getResultArray();

        $slugFor = static function (string $name): string {
            $s = strtolower(trim($name));
            $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
            $s = trim($s, '-');
            return $s === '' ? 'tag' : substr($s, 0, 80);
        };

        // First pass: assign slugs, ensuring uniqueness within the same parent.
        $slugByParent = []; // parentId|null => [slug => true]
        $slugById     = []; // id => slug
        foreach ($rows as $r) {
            $pidKey = $r['ParentID'] === null ? 'root' : (string) $r['ParentID'];
            $base   = $slugFor((string) $r['Name']);
            $slug   = $base;
            $i = 2;
            while (isset($slugByParent[$pidKey][$slug])) {
                $slug = substr($base, 0, 76) . '-' . $i;
                $i++;
            }
            $slugByParent[$pidKey][$slug] = true;
            $slugById[(int) $r['MarketID']] = $slug;
        }

        // Second pass: compute Path/Depth via parent walk (memoized).
        $byId = [];
        foreach ($rows as $r) $byId[(int) $r['MarketID']] = $r;

        $pathDepth = [];
        $compute = function (int $id) use (&$compute, &$pathDepth, $byId, $slugById) {
            if (isset($pathDepth[$id])) return $pathDepth[$id];
            $row  = $byId[$id];
            $slug = $slugById[$id];
            $pid  = $row['ParentID'] !== null ? (int) $row['ParentID'] : null;
            if ($pid === null || !isset($byId[$pid])) {
                $pathDepth[$id] = ['/' . $slug, 0];
            } else {
                [$pp, $pd] = $compute($pid);
                $pathDepth[$id] = [$pp . '/' . $slug, $pd + 1];
            }
            return $pathDepth[$id];
        };

        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            $id = (int) $r['MarketID'];
            [$path, $depth] = $compute($id);
            $db->table('markets')->where('MarketID', $id)->update([
                'Slug'   => $slugById[$id],
                'Path'   => $path,
                'Depth'  => $depth,
                'Active' => 1,
                'Sort'   => 0,
                'Added'  => $now,
                'Stamp'  => $now,
            ]);
        }

        // 4) Now that Slug/Path are populated, enforce NOT NULL + indexes/FK.
        $forge->modifyColumn('markets', [
            'Slug' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'Path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
        ]);

        // Indexes — wrap in try/catch in case a partial run already added them.
        try { $db->query('ALTER TABLE markets ADD UNIQUE KEY uk_markets_slug_parent (ParentID, Slug)'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE markets ADD KEY idx_markets_parent (ParentID)'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE markets ADD KEY idx_markets_path (Path)'); } catch (\Throwable $e) {}

        // Self-referential FK on ParentID -> MarketID. RESTRICT to mirror bundle.
        try {
            $db->query('ALTER TABLE markets ADD CONSTRAINT fk_markets_parent FOREIGN KEY (ParentID) REFERENCES markets(MarketID) ON DELETE RESTRICT ON UPDATE CASCADE');
        } catch (\Throwable $e) {
            // FK may already exist or legacy data may violate it; surface to logs.
            log_message('warning', 'fk_markets_parent not added: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $db = Database::connect();

        // Drop FK + indexes (ignore errors if absent).
        try { $db->query('ALTER TABLE markets DROP FOREIGN KEY fk_markets_parent'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE markets DROP INDEX uk_markets_slug_parent'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE markets DROP INDEX idx_markets_parent'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE markets DROP INDEX idx_markets_path'); } catch (\Throwable $e) {}

        // Drop the new columns.
        $this->forge->dropColumn('markets', ['Slug', 'Path', 'Depth', 'Active', 'Sort', 'Added', 'Stamp']);

        // Rename columns back to the legacy names.
        $this->forge->modifyColumn('markets', [
            'MarketID' => [
                'name'           => 'ID',
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Name' => [
                'name'       => 'Market',
                'type'       => 'VARCHAR',
                'constraint' => 256,
                'null'       => true,
            ],
        ]);
    }
}
