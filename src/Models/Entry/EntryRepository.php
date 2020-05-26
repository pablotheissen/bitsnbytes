<?php

declare(strict_types=1);

namespace Bitsnbytes\Models\Entry;

use Bitsnbytes\Models\Model;
use Bitsnbytes\Models\Tag\Tag;
use Bitsnbytes\Models\Tag\TagNotFoundException;
use Bitsnbytes\Models\Tag\TagRepository;
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
     * Fetch a single entry from the database based on the slug parameter. Throws an exception if slug doesn't exist in
     * table.
     *
     * @param string $slug Slug to search for in <tt>entries</tt> table.
     *
     * @return Entry Valid entry with tags
     * @throws EntryNotFoundException If query doesn't return any rows, EntryNotFoundException is thrown.
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
     * Convert the associative array returned from PDOStatement::fetch into an instance of <tt>Entry</tt>.
     *
     * @param array<string> $query_result <p>Result from PDOStatement::fetch which <i>must</i> contain the keys
     *                                    <p><b>eid</b> ID of tag
     *                                    <p><b>slug</b> Slug of entry
     *                                    <p><b>title</b> User-readable title
     *                                    <p><b>url</b> URL to which entry links
     *                                    <p><b>text</b> Main content of entry
     *                                    <p>Result <i>may</i> contain the key
     *                                    <p><b>date</b> [optional] DateTime string in format
     *                                    YYYY-MM-DD<i>T</i>HH:MM:SS+00:00
     *
     * @return Entry Valid entry with unchanged <tt>$query_result</tt> data
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
     * Fetch the latest entries from the database.
     *
     * @param bool $returnAsArray If <b>true</b>, <tt>Entry::toArray()</tt> is called for each entry returned.
     *
     * @return array<int,array<array<array<int|string|null>>|DateTime|int|string|null>|Entry> List of valid entries
     *                                                                                        inlcuding tags. If
     *                                                                                        <tt>$returnAsArray</tt>
     *                                                                                        is <b>true</b> this will
     *                                                                                        return a nested array,
     *                                                                                        otherwise it will return
     *                                                                                        <b>array&lt;Entry&gt;</b>
     * @throws Exception
     *
     * @todo Add limit to number of entries or date or both
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
     * Fetch the latest entries that are tagged with <tt>$tag</tt> from the database.
     *
     * @param Tag  $tag
     * @param bool $returnAsArray
     *
     * @return array<int,array<array<array<int|string|null>>|DateTime|int|string|null>|Entry>
     * @throws Exception
     */
    public function fetchEntriesByTag(Tag $tag, bool $returnAsArray = false): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT entries.eid, title, slug, url, text, date
            FROM entries
            LEFT JOIN entry_tag et on entries.eid = et.eid
            WHERE et.tid = :tid
            ORDER BY date DESC'
        );
        $stmt->bindParam(':tid', $tag->tid, PDO::PARAM_INT);
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
     * Update data of entry.
     * <p><i>Notice:</i> Tags withing <tt>$entry</tt> are not stored to the database. Call
     * EntryRepository::updateTagsByEntry separately.
     *
     * @param string $slug  Slug of entry as currently used in database.
     * @param Entry  $entry New data to be stored in database.
     *                      <p><i>Notice:</i> Entry must at least contain a <b>title</b> and a <b>slug</b>,
     *                      otherwise an exception will be called.
     *
     * @return bool  <b>TRUE</b> if successful, <b>FALSE</b> on failure
     * @throws Exception
     */
    public function updateBySlug(string $slug, Entry $entry): bool
    {
        $sql =
            'UPDATE entries
            SET
                title = :title,
                slug = :slug,
                url = :url,
                text = :text,
                date = :date
            WHERE slug = :oldslug';
        return $this->writeEntryDataToDatabase($sql, $entry, $slug);
    }

    /**
     * Helper function for EntryRepository::createNewEntry and EntryRepository::updateBySlug for writing
     * (inserting/updating) entry data to the database and checking for errors after writing.
     *
     * @param string      $sql
     * @param Entry       $entry
     * @param string|null $old_slug
     *
     * @return bool
     * @throws Exception
     */
    private function writeEntryDataToDatabase(string $sql, Entry $entry, string $old_slug = null)
    {
        $stmt = $this->pdo->prepare($sql);
        if ($old_slug !== null) {
            $stmt->bindParam(':oldslug', $old_slug, PDO::PARAM_STR);
        }
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
        throw new Exception(
            __FILE__ . ':' . __LINE__ . ":\nSQL Error " . $stmt->errorInfo()[0] . ":\n" . $stmt->errorInfo()[2]
        );
    }

    /**
     * Store new entry in database.
     *
     * @param Entry $entry Entry to insert into database.
     *                     <p><i>Notice:</i> Entry must at least contain a <b>title</b> and a <b>slug</b>,
     *                     otherwise an exception will be called.
     *
     * @return bool
     * @throws Exception
     */
    public function createNewEntry(Entry $entry): bool
    {
        $sql =
            'INSERT INTO entries
                (title, slug, url, text, date)
            VALUES
                (:title, :slug, :url, :text, :date)';
        return $this->writeEntryDataToDatabase($sql, $entry);
    }

    public function checkIfSlugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT slug FROM entries WHERE slug = :slug');
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    /**
     * Update the tags related to an entry based on a list of tag names.
     * <p>If the tags don't exist, they are created with the help of TagRepository::createNewTagFromTitle()
     * <p>This method deletes all relations for a given entry from entry_tag table and create new rows for each tag.
     *
     * @param Entry         $entry Entry to wich the <tt>$tags</tt> should be added. Existing tags in this instance of
     *                             <tt>Entry</tt> are ignored. Entry must have <tt>Entry::eid</tt> set.
     * @param array<string> $tags  List of tag titles as strings. Tags are looked up or created automatically.
     *
     * @return Entry Returns the entry submitted through <tt>$entry</tt> parameter but with the actual tags added to
     *               <tt>Entry::tags</tt>.
     * @throws Exception
     */
    public function updateTagsByEntry(Entry $entry, array $tags): ?Entry
    {
        // Use transaction as we only want to delete the tag-entry relationships if we can add them successfully later on
        $this->pdo->beginTransaction();

        // Remove all existing relationships from table entry_tag ...
        $entry = $this->removeTagRelationshipsByEntry($entry);

        if ($entry === null) {
            $this->pdo->rollBack();
            return null; // TODO: throw exception instead of return null?
        }

        // ... and (re-)create the relationships again
        foreach ($tags as $tag_title) {
            try {
                $tag = $this->tag_repository->fetchTagByTitle($tag_title);
            } catch (TagNotFoundException $e) {
                $tag = $this->tag_repository->createNewTagFromTitle($tag_title);
            }
            $entry->tags[] = $tag;

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
     * @return Entry|null  <p><b>Entry</b> on success, same entry as submitted via @param <tt>$entry</tt> but without any
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

    /**
     * @param Tag[] $tags
     * @param bool  $or_disjunction
     * @param bool  $return_as_array
     *
     * @return array<Entry|mixed>
     * @throws Exception
     */
    public function fetchEntriesByTags(array $tags, bool $or_disjunction = false, bool $return_as_array = false): array
    {
        if (count($tags) === 0) {
            // return empty array if there aren't any tags to look for
            return [];
        }
        $sql = 'SELECT entries.eid, title, slug, url, text, date
            FROM entries
            LEFT JOIN entry_tag et on entries.eid = et.eid
            WHERE ';
        $where = [];
        $tag_ids = [];
        $i = 0;
        foreach ($tags as $tag) {
            $where[] = 'et.tid = :tid' . strval($i);
            $tag_ids[':tid' . strval($i)] = $tag->tid;
            $i++;
        }
        if ($or_disjunction === true) {
            $sql .= implode(' OR ', $where);
        } else {
            $sql .= implode(' AND ', $where);
        }
        $sql .= "
            GROUP BY entries.eid
            ORDER BY date DESC";
        // TODO: Order by number of matching entries with OR

        $stmt = $this->pdo->prepare($sql);
        foreach ($tag_ids as $key => &$value) {
            // $value has to be passed by reference, otherwise bindParam doesnt work
            // https://www.php.net/manual/fr/pdostatement.bindparam.php#98145
            $stmt->bindParam($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();


        $entries = [];
        if ($return_as_array === true) {
            while ($rslt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = $this->convertAssocToEntry($rslt);
                $entry->tags = $this->tag_repository->findTagsByEntries($entry);
                $entries[] = $entry->toArray();
            }
        } else {
            while ($rslt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = $this->convertAssocToEntry($rslt);
                $entry->tags = $this->tag_repository->findTagsByEntries($entry);
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param string $query
     * @param bool   $or_disjunction
     * @param bool   $return_as_array
     *
     * @return array<Entry|mixed>
     * @throws Exception
     */
    public function findEntriesMatchingTags(
        string $query,
        bool $or_disjunction = false,
        bool $return_as_array = false
    ): array {
        if (strlen($query) === 0) {
            // return empty array if search query is empty
            return [];
        }
        $query_segments = explode(' ', trim($query));
        $sql = 'SELECT eid, title, slug, url, text, date
            FROM entries
            WHERE ';
        $where = [];
        $segment_ids = [];
        $i = 0;
        foreach ($query_segments as $query_segment) {
            $where[] = 'title LIKE :segment' . strval($i);
            $segment_ids[':segment' . strval($i)] = '%' . $query_segment . '%';
            $i++;
        }
        if ($or_disjunction === true) {
            $sql .= implode(' OR ', $where);
        } else {
            $sql .= implode(' AND ', $where);
        }
        $sql .= "
            GROUP BY eid
            ORDER BY date DESC";

        $stmt = $this->pdo->prepare($sql);
        foreach ($segment_ids as $key => &$value) {
            // $value has to be passed by reference, otherwise bindParam doesnt work
            // https://www.php.net/manual/fr/pdostatement.bindparam.php#98145
            $stmt->bindParam($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        $entries = [];
        if ($return_as_array === true) {
            while ($rslt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = $this->convertAssocToEntry($rslt);
                $entry->tags = $this->tag_repository->findTagsByEntries($entry);
                $entries[] = $entry->toArray();
            }
        } else {
            while ($rslt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = $this->convertAssocToEntry($rslt);
                $entry->tags = $this->tag_repository->findTagsByEntries($entry);
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}