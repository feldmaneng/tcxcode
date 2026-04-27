<?php
namespace App\Libraries;

use App\Models\MarketModel;
use Config\Database;

/**
 * Helpers for the hierarchical markets tree.
 *
 * Tree storage is materialized-path: each node stores Path = "/slug-a/slug-b"
 * and Depth = number of segments. Subtree queries become a single
 *   WHERE Path LIKE '/slug-a/slug-b/%'
 * Cycles are prevented by checking that a candidate parent is not a descendant
 * of the node being moved.
 */
class MarketTree
{
    public static function slugify(string $name): string
    {
        $s = strtolower(trim($name));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        $s = trim($s, '-');
        return $s === '' ? 'tag' : substr($s, 0, 80);
    }

    /**
     * Compute Path & Depth for a node given its (possibly null) parent ID.
     * Returns [string $path, int $depth].
     */
    public static function computePath(?int $parentId, string $slug): array
    {
        if ($parentId === null) {
            return ['/' . $slug, 0];
        }
        $parent = (new MarketModel())->find($parentId);
        if (!$parent) {
            return ['/' . $slug, 0];
        }
        $parentPath = (string) $parent['Path'];
        return [$parentPath . '/' . $slug, ((int) $parent['Depth']) + 1];
    }

    /**
     * Return all descendant IDs of $id (NOT including $id itself).
     */
    public static function descendantIds(int $id): array
    {
        $model = new MarketModel();
        $node = $model->find($id);
        if (!$node) return [];
        $rows = $model->builder()
            ->select('MarketID')
            ->like('Path', $node['Path'] . '/', 'after')
            ->get()->getResultArray();
        return array_map(fn($r) => (int) $r['MarketID'], $rows);
    }

    /**
     * Return $id PLUS all descendant IDs.
     */
    public static function subtreeIds(int $id): array
    {
        return array_merge([$id], self::descendantIds($id));
    }

    /**
     * Move a node under $newParentId. Recomputes Path/Depth for the node and
     * every descendant atomically. Throws on cycle.
     *
     * Returns true on success.
     */
    public static function move(int $id, ?int $newParentId): bool
    {
        $model = new MarketModel();
        $node = $model->find($id);
        if (!$node) return false;

        if ($newParentId !== null) {
            if ($newParentId === $id) {
                throw new \RuntimeException('cycle: cannot parent a node to itself');
            }
            $descendants = self::descendantIds($id);
            if (in_array($newParentId, $descendants, true)) {
                throw new \RuntimeException('cycle: cannot move under own descendant');
            }
        }

        $oldPath  = (string) $node['Path'];
        $segments = explode('/', $oldPath);
        $slug     = end($segments) ?: $node['Slug'];

        [$newPath, $newDepth] = self::computePath($newParentId, $slug);
        if ($newPath === $oldPath) return true;

        $db = Database::connect();
        $db->transStart();

        // Update self
        $model->update($id, [
            'ParentID' => $newParentId,
            'Path'     => $newPath,
            'Depth'    => $newDepth,
        ]);

        // Update descendants (rewrite Path prefix, adjust Depth)
        $depthDelta = $newDepth - (int) $node['Depth'];
        $descendants = $model->builder()
            ->like('Path', $oldPath . '/', 'after')
            ->get()->getResultArray();

        foreach ($descendants as $d) {
            $rewritten = $newPath . substr((string) $d['Path'], strlen($oldPath));
            $db->table('markets')->where('MarketID', $d['MarketID'])->update([
                'Path'  => $rewritten,
                'Depth' => ((int) $d['Depth']) + $depthDelta,
            ]);
        }

        $db->transComplete();
        return $db->transStatus();
    }
}
