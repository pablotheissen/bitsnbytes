<?php

declare(strict_types=1);


namespace Bitsbytes\Models\Tag;


use Bitsbytes\Models\Entry;
use Bitsbytes\Models\Model;
use PDO;

class TagRepository extends Model
{
    /**
     * @param string $slug
     *
     * @return Tag|null
     */
    public function fetchTagBySlug(string $slug): ?Tag
    {
        $stmt = $this->pdo->prepare('SELECT tid, slug, title FROM tags WHERE slug = :slug');
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rslt === false) {
            return null;
        }

        return $this->createTagFromAssoc($rslt);
    }

    /**
     * @param array<string> $query_result
     *
     * @return Tag
     */
    private function createTagFromAssoc(array $query_result): Tag
    {
        return new Tag(
            intval($query_result['tid']),
            $query_result['slug'],
            $query_result['title'],
        );
    }

    /**
     * @param array<Entry>|Entry $entries
     *
     * @return array<Tag>
     */
    public function findTagsByEntries($entries): array
    {
        if ($entries instanceof Entry) {
            $entries = [$entries];
        }

        // Get all IDs from entries for easier SQL queries
        $entry_ids = [];
        foreach ($entries as $entry) {
            // ID can be null, if entry was created at runtime and not fetched from the db, so we'll filter these
            // 'empty' entries
            if ($entry->eid !== null) {
                $entry_ids[] = $entry->eid;
            }
        }

        // No or no valid entries available? --> No matching tags available, early return!
        if (empty($entry_ids)) {
            return [];
        }

        // remove duplicates and renumber array keys so that keys can be used for PDOStatement::bindValue()
        $entry_ids = array_values(array_unique($entry_ids));

        $conditions = array_fill(0, count($entry_ids), 'et.eid = ?');
        $sql_where = implode(' OR ', $conditions);
        $sql = 'SELECT tags.tid, slug, title
            FROM tags
                     LEFT JOIN entry_tag et on tags.tid = et.tid
            WHERE ' . $sql_where . '
            GROUP BY tags.tid
            ORDER BY title ASC';
        $stmt = $this->pdo->prepare($sql);
        foreach ($entry_ids as $key => $entry_id) {
            $stmt->bindValue(($key + 1), $entry_id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $tags = [];
        while ($row = $stmt->fetch()) {
            $tags[] = $this->createTagFromAssoc($row);
        }
        return $tags;
    }
}