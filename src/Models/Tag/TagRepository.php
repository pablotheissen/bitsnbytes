<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\Tag;


use Bitsnbytes\Models\DuplicateKeyException;
use Bitsnbytes\Models\Entry\Entry;
use Bitsnbytes\Models\Model;
use Exception;
use PDO;

class TagRepository extends Model
{
    /**
     * Fetch a single tag from the database based on the slug parameter. Throws an exception if slug doesn't exist in
     * table.
     *
     * @param string $slug Slug to search for in <tt>tags</tt> table.
     *
     * @return Tag Valid tag with tid, slug and title
     * @throws TagNotFoundException If query doesn't return any rows, TagNotFoundException is thrown.
     */
    public function fetchTagBySlug(string $slug): Tag
    {
        $stmt = $this->pdo->prepare('SELECT tid, slug, title FROM tags WHERE slug = :slug');
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rslt === false) {
            throw new TagNotFoundException();
        }

        return $this->convertAssocToTag($rslt);
    }

    /**
     * Convert the associative array returned from PDOStatement::fetch into an instance of <tt>Tag</tt>.
     *
     * @param array<string> $query_result <p>Result from PDOStatement::fetch which <i>must</i> contain the keys
     *                                    <p><b>tid</b> ID of tag
     *                                    <p><b>slug</b> Slug of tag
     *                                    <p><b>title</b> User-readable title
     *
     * @return Tag Valid tag with tid, slug and title unchanged from <tt>$query_result</tt> data
     */
    private function convertAssocToTag(array $query_result): Tag
    {
        return new Tag(
            intval($query_result['tid']),
            $query_result['slug'],
            $query_result['title'],
        );
    }

    /**
     * Fetch all data of a tag for a given title from the database and return instance of Tag.
     *
     * @param string $title User-readable title to look for in the database
     *
     * @return Tag <b>Tag</b> with id und slug if title was found in database
     * @throws TagNotFoundException If query doesn't return any rows, TagNotFoundException is thrown.
     */
    public function fetchTagByTitle(string $title): Tag
    {
        $stmt = $this->pdo->prepare('SELECT tid, slug, title FROM tags WHERE title = :title');
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rslt === false) {
            throw new TagNotFoundException();
        }

        return $this->convertAssocToTag($rslt);
    }

    /**
     * Convert title to a valid slug and insert new tag into database. This will create an isolated tag without any
     * relationships to existing entries.
     *
     * @param string $title
     *
     * @return Tag Instance of <tt>Tag</tt> with unchanged title but new id and slug for this tag.
     * @throws Exception If any error occurs during inserting new data into database.
     */
    public function createNewTagFromTitle(string $title): Tag
    {
        // TODO: check if slug exists and refine method of creating slug (currently in Controller.php)
        $tag = new Tag(null, str_replace(' ', '-', strtolower($title)), $title);
        $this->createNewTag($tag);

        // createNewTag() throws exception if not successful, so we won't check here again
        $tag->tid = (int)$this->pdo->lastInsertId();
        return $tag;
    }

    /**
     * Insert new tag into database by submitting Tag instance with an empty id.
     *
     * @param Tag $tag Instance of Tag with title and slug filled. Tag id is ignored. The slug must not already exist.
     *                 If it already exists this method will throw an Exception.
     *
     * @return bool <b>TRUE</b> if tag was created without SQL error, <b>FALSE</b> on error.
     * @throws DuplicateKeyException Slug included in <tt>$tag</tt> already exists in database.
     * @throws Exception On any other error of the sql statement, including duplicate tag title.
     *
     * @todo Check for duplicate title
     */
    public function createNewTag(Tag $tag): bool
    {
        if ($tag->slug === '' || $tag->slug === null || $tag->title === '' || $tag->title === null) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO tags
                (title, slug)
            VALUES
                (:title, :slug)'
        );

        $stmt->bindParam(':title', $tag->title, PDO::PARAM_STR);
        $stmt->bindParam(':slug', $tag->slug, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->errorInfo()[0] === '00000') {
            return true;
        }

        if ($this->checkIfSlugExists($tag->slug)) {
            throw new DuplicateKeyException();
        }
        throw new Exception($stmt->errorInfo()[2]);
    }

    /**
     * Check if there already is a tag with this slug.
     *
     * @param string $slug Slug to look up in the database
     *
     * @return bool <b>TRUE</b> if tag with this slug already exists, <b>FALSE</b> if no tag with this slug exists.
     */
    public
    function checkIfSlugExists(
        string $slug
    ): bool {
        $stmt = $this->pdo->prepare('SELECT slug FROM tags WHERE slug = :slug');
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    /**
     * <p>Get all tags in alphabetical order (by <tt>Tag::title</tt>) for a set of entries or for a single entry.
     * <p>Only returns a set of tags, duplicate tags (e.g. with to entries pointing to the same tag) are removed.
     *
     * @param array<Entry>|Entry $entries Array of entries or single entry for which tags are searched and returned.
     *                                    <p>All entries without a valid id (<tt>Entry::eid</tt>) are ignored.
     *
     * @return array<Tag> Array of all tags found for the list of entries
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
        if (count($entry_ids) === 0) {
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
            $tags[] = $this->convertAssocToTag($row);
        }
        return $tags;
    }
}