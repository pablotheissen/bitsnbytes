<?php

declare(strict_types=1);

namespace Bitsbytes\Models;

use DateTime;
use Exception;
use PDO;
use DateTimeInterface;

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
     * @param bool $returnAsArray
     *
     * @return array<Entry|array<int|string|DateTime|null>>
     * @throws Exception
     */
    public function findLatestEntries(bool $returnAsArray = false): array
    {
        $stmt = $this->pdo->prepare('SELECT eid, title, slug, url, text, date FROM entries ORDER BY date DESC');
//        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();

        $entries = [];
        if ($returnAsArray === true) {
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

    /**
     * @param string $slug
     * @param Entry  $entry
     *
     * @return bool
     * @throws Exception
     */
    public function updateBySlug(string $slug, Entry $entry): bool
    {
        $stmt = $this->pdo->prepare('UPDATE entries
            SET
                title = :title,
                slug = :newslug,
                url = :url,
                text = :text,
                date = :date
            WHERE slug = :oldslug'
        );
        $stmt->bindParam(':oldslug', $slug, PDO::PARAM_STR);
        $stmt->bindParam(':title', $entry->title, PDO::PARAM_STR);
        $stmt->bindParam(':newslug', $entry->slug, PDO::PARAM_STR);
        $stmt->bindParam(':url', $entry->url, PDO::PARAM_STR);
        $stmt->bindParam(':text', $entry->text, PDO::PARAM_STR);
        if($entry->date instanceof DateTimeInterface) {
            $date_atom = $entry->date->format(DateTimeInterface::ATOM);
        } else {
            $date_atom = (new DateTime('now'))->format(DateTimeInterface::ATOM);
        }
        $stmt->bindParam(':date', $date_atom, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->errorInfo()[0] === '00000') {
            return true;
        }
        throw new Exception($stmt->errorInfo()[2]);
    }
}