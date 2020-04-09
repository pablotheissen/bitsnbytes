<?php

declare(strict_types=1);

namespace Bitsbytes\Models;

use DateTime;
use Exception;
use PDO;
use phpDocumentor\Reflection\Types\Array_;

class EntryRepository extends Model
{
    /**
     * @param string $slug
     *
     * @return Entry
     * @throws Exception
     */
    public function findEntryBySlug(string $slug)
    {
        $stmt = $this->pdo->prepare('SELECT eid, title, slug, url, text, date FROM entries WHERE slug = :slug');
        // TODO: Data filtering? https://phptherightway.com/#data_filtering
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        $entry = $this->createEntryFromAssoc($rslt);
        return $entry;
    }

    /**
     * @param array<string> $query_result
     *
     * @return Entry
     * @throws Exception
     */
    private function createEntryFromAssoc(array $query_result): Entry
    {
        $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+e', $query_result['date']);
        if ($datetime === false) {
            $datetime = new DateTime("now");
        }
        return new Entry(
            intval($query_result['eid']),
            $query_result['title'],
            $query_result['slug'],
            $query_result['url'],
            $query_result['text'],
            $datetime
        );
    }

    /**
     * @return array<Entry|array<int|string|DateTime>>
     * @throws Exception
     */
    public function findLatestEntries(bool $returnAsArray = false): array
    {
        $stmt = $this->pdo->prepare('SELECT eid, title, slug, url, text, date FROM entries ORDER BY date DESC');
        // TODO: Data filtering? https://phptherightway.com/#data_filtering
//        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();

        $entries = [];
        if($returnAsArray === true) {
            while ($rslt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromAssoc($rslt)->toArray();
            }
        } else {
            while ($rslt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromAssoc($rslt);
            }
        }

        return $entries;
    }
}