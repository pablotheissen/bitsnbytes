<?php

declare(strict_types=1);

namespace Bitsbytes\Models;

use Bitsbytes\Models\Tag\TagRepository;
use DateTime;
use DateTimeInterface;
use Exception;
use PDO;

class EntryRepository extends Model
{
    private TagRepository $tag_repository;

    public function __construct(TagRepository $tag_repository, PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tag_repository = $tag_repository;
    }

    /**
     * @param string $slug
     *
     * @return Entry
     * @throws EntryNotFoundException
     * @throws Exception
     */
    public function fetchEntryBySlug(string $slug): Entry
    {
        $stmt = $this->pdo->prepare('SELECT eid, title, slug, url, text, date FROM entries WHERE slug = :slug');
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rslt === false) {
            throw new EntryNotFoundException();
        }

        $entry = $this->convertAssocToEntry($rslt);
        $entry->tags = $this->tag_repository->findTagsByEntries($entry);

        return $entry;
    }

    /**
     * @param array<string> $query_result
     *
     * @return Entry
     * @throws Exception
     */
    private function convertAssocToEntry(array $query_result): Entry
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
     * @return array<int,array<array<array<int|string|null>>|DateTime|int|string|null>|Entry>
     * @throws Exception
     */
    public function fetchLatestEntries(bool $returnAsArray = false): array
    {
        $stmt = $this->pdo->prepare('SELECT eid, title, slug, url, text, date FROM entries ORDER BY date DESC');
        $stmt->execute();

        $entries = [];
        if ($returnAsArray === true) {
            while ($rslt = $stmt->fetch()) {
                $entry = $this->convertAssocToEntry($rslt);
                $entry->tags = $this->tag_repository->findTagsByEntries($entry);
                $entries[] = $entry->toArray();
            }
        } else {
            while ($rslt = $stmt->fetch()) {
                $entry = $this->convertAssocToEntry($rslt);
                $entry->tags = $this->tag_repository->findTagsByEntries($entry);
                $entries[] = $entry;
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
        $stmt = $this->pdo->prepare(
            'UPDATE entries
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
        if ($entry->date instanceof DateTimeInterface) {
            $date_atom = $entry->date->format(DateTimeInterface::ATOM);
        } else {
            $date_atom = (new DateTime('now'))->format(DateTimeInterface::ATOM);
        }
        $stmt->bindParam(':date', $date_atom, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->errorInfo()[0] === '00000') {
            return true;
        }
        throw new Exception($stmt->errorInfo()[2]);
    }

    /**
     * @param Entry $entry
     *
     * @return bool
     * @throws Exception
     */
    public function createNewEntry(Entry $entry): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO entries
                (title, slug, url, text, date)
            VALUES
                (:title, :slug, :url, :text, :date)'
        );
        $stmt->bindParam(':title', $entry->title, PDO::PARAM_STR);
        $stmt->bindParam(':slug', $entry->slug, PDO::PARAM_STR);
        $stmt->bindParam(':url', $entry->url, PDO::PARAM_STR);
        $stmt->bindParam(':text', $entry->text, PDO::PARAM_STR);
        if ($entry->date instanceof DateTimeInterface) {
            $date_atom = $entry->date->format(DateTimeInterface::ATOM);
        } else {
            $date_atom = (new DateTime('now'))->format(DateTimeInterface::ATOM);
        }
        $stmt->bindParam(':date', $date_atom, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->errorInfo()[0] === '00000') {
            return true;
        }
        throw new Exception($stmt->errorInfo()[2]);
    }

    public function checkIfSlugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT slug FROM entries WHERE slug = :slug');
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    /**
     * @param Entry         $entry
     * @param array<string> $tags
     *
     * @return Entry
     * @throws Exception
     */
    public function updateTagsByEntry(Entry $entry, array $tags): ?Entry
    {
        $this->pdo->beginTransaction();
        $entry = $this->removeTagRelationshipsByEntry($entry);

        if ($entry === null) {
            $this->pdo->rollBack();
            return null; // TODO: throw exception instead of return null?
        }

        foreach ($tags as $tag_title) {
            $tag = $this->tag_repository->fetchTagByTitle($tag_title);
            if ($tag === null) {
                $tag = $this->tag_repository->createNewTagFromTitle($tag_title);
            }
            $entry->tags[] = $tag;
            var_dump($tag);
            $stmt = $this->pdo->prepare(
                'INSERT INTO entry_tag
                    (eid, tid)
                VALUES
                    (:eid, :tid)'
            );
            $stmt->bindParam(':eid', $entry->eid, PDO::PARAM_INT);
            $stmt->bindParam(':tid', $tag->tid, PDO::PARAM_INT);
            $stmt->execute();
        }
        $this->pdo->commit();
        return $entry;
    }

    /**
     * Remove all <b>relationships</b> between entries and tags for a specific entry. This will just update the table
     * entry_tag, not actually delete any tags from the according table.
     *
     * @param Entry $entry The entry of which all tag relationships should be removed.
     *
     * @return Entry|null <p><b>Entry</b> on success, same entry as submitted via @param <tt>$entry</tt> but without any
     *                    tags.
     *                    <p>If this entry doesn't have any associated tags in the database, nothing will be deleted
     *                    and the entry will be returned without a error.
     *                    <p><b>NULL</b> on error in case of any SQL/database error.
     */
    private function removeTagRelationshipsByEntry(Entry $entry): ?Entry
    {
        $stmt = $this->pdo->prepare('DELETE FROM entry_tag WHERE eid = :eid');
        $stmt->bindParam(':eid', $entry->eid, PDO::PARAM_INT);
        $success = $stmt->execute();
        if ($success === false) {
            return null;
        }
        $entry->tags = [];
        return $entry;
    }
}