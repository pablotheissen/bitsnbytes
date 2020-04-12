<?php

declare(strict_types=1);


namespace Bitsbytes\Models\Tag;


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
            $this->pdo,
        );
    }
}