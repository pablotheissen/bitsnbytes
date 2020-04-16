<?php

declare(strict_types=1);

namespace Bitsnbytes\Controllers;

use AltoRouter;
use Bitsnbytes\Models\Entry\Entry;
use Bitsnbytes\Models\Entry\EntryNotFoundException;
use Bitsnbytes\Models\Entry\EntryRepository;
use Bitsnbytes\Models\Tag\TagNotFoundException;
use Bitsnbytes\Models\Tag\TagRepository;
use Bitsnbytes\Models\Template\Renderer;
use DateTimeInterface;
use Erusev\Parsedown\Parsedown;
use Exception;
use Http\Request;
use Http\Response;

class EntryController extends Controller
{
    private EntryRepository $entry_repository;
    private AltoRouter $router;
    private Parsedown $parsedown;
    private TagRepository $tag_repository;

    public function __construct(
        EntryRepository $entry_repository,
        TagRepository $tag_repository,
        AltoRouter $router,
        Parsedown $parsedown,
        Request $request,
        Response $response,
        Renderer $renderer
    ) {
        parent::__construct($request, $response, $renderer);
        $this->entry_repository = $entry_repository;
        $this->tag_repository = $tag_repository;
        $this->router = $router;
        $this->parsedown = $parsedown;
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function showBySlug(array $params): void
    {
        try {
            $entry = $this->entry_repository->fetchEntryBySlug($params['slug']);
        } catch (EntryNotFoundException $e) {
            $this->response->setContent('404 - Page not found');
            $this->response->setStatusCode(404);
            return;
        }

        $data = $entry->toArray();

        $html = $this->renderer->render('Entry', $data);
        $this->response->setContent($html);
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function showByTag(array $params): void
    {
        try {
            $tag = $this->tag_repository->fetchTagBySlug($params['tag']);
        } catch (TagNotFoundException $e) {
            $this->response->setContent('404 - Page not found');
            $this->response->setStatusCode(404);
            return;
        }

        $entries = $this->entry_repository->fetchEntriesByTag($tag, true);
        array_walk(
            $entries,
            function (&$entry): void {
                $entry['url-edit'] = $this->router->generate('edit-entry', ['slug' => $entry['slug']]);
                $entry['text'] = $this->parsedown->toHtml($entry['text']);
            }
        );

        $data = ['entries' => $entries, 'heading' => 'Entries tagged as ›' . $tag->title . '‹'];

        $html = $this->renderer->render('entrylist', $data);
        $this->response->setContent($html);
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function showLatest(array $params): void
    {
        $entries = $this->entry_repository->fetchLatestEntries(true);
        array_walk(
            $entries,
            function (&$entry): void {
                $entry['url-edit'] = $this->router->generate('edit-entry', ['slug' => $entry['slug']]);
                $entry['text'] = $this->parsedown->toHtml($entry['text']);
            }
        );
        $html = $this->renderer->render('entrylist', ['entries' => $entries]);
        $this->response->setContent($html);
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function editformBySlug(array $params): void
    {
        try {
            $entry = $this->entry_repository->fetchEntryBySlug($params['slug']);
        } catch (EntryNotFoundException $e) {
            $this->response->setContent('404 - Page not found');
            $this->response->setStatusCode(404);
            return;
        }

        $html = $this->renderer->render('editentry', $entry->toArray());
        $this->response->setContent($html);
    }

    /**
     * @param array<string> $params
     */
    public function newform(array $params): void
    {
        $html = $this->renderer->render('editentry');
        $this->response->setContent($html);
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function saveEntry(array $params): void
    {
        $error_fields = [];
        $error_messages = [];

        $new_title = $this->request->getBodyParameter('title');
        if ($new_title === '' || $new_title === null) {
            $error_fields[] = 'title';
            $error_messages['title'] = 'Entry must have a title.';
        }
        $user_slug = $this->request->getBodyParameter('slug');
        $new_slug = $this->filterSlug($user_slug);
        if ($user_slug === '' || $user_slug === null || $new_slug === '' || $new_slug === null) {
            $new_slug = $this->createSlugFromTitle($new_title);
        } elseif ($user_slug != $new_slug) {
            $error_fields[] = 'slug';
            if (mb_strlen($user_slug) > 30) {
                $error_messages['slug'] = 'Slug too long. Maximum of 30 characters.';
            } else {
                $error_messages['slug'] = 'Only use characters <em>a-z</em>, <em>0-9</em>, <em>_</em> and <em>-</em>.';
            }
        }
        $new_url = $this->filterUrl($this->request->getBodyParameter('url'));
        $new_text = $this->request->getBodyParameter('text');
        $new_date = $this->filterDate($this->request->getBodyParameter('date'));
        if ($new_date === null) {
            $error_fields[] = 'date';
        } elseif ($new_date === '') {
            $new_date = date('Y-m-d');
        }
        $new_time = $this->filterTime($this->request->getBodyParameter('time'));
        if ($new_time === null) {
            $error_fields[] = 'time';
        } elseif ($new_time === '') {
            $new_time = date('H:i:s');
        }
        $new_datetime = $this->createDateTimeFromDateAndTime($new_date, $new_time);
        if ($new_datetime === null) {
            $error_fields[] = 'date';
            $error_fields[] = 'time';
        }
        // TODO: parse/filter tags
        $new_tags = array_filter($this->request->getBodyParameter('tags'));

        if (count($error_fields) > 0) {
            $this->editformErrorsFound(
                array_unique($error_fields),
                $error_messages,
                $user_slug,
                $new_title,
                $new_url,
                $new_text,
                $new_datetime
            );
            return;
        }

        $entry = new Entry(null, $new_title, $new_slug, $new_url, $new_text, $new_datetime);

        if (isset($params['slug'])) { // UPDATE existing entry
            // TODO catch exception when error during update occurs
            $success = $this->entry_repository->updateBySlug($params['slug'], $entry);
        } else { // NEW entry
            $success = $this->entry_repository->createNewEntry($entry);
        }

        // Fetch entry id from database by simply fetching the entire entry again
        $entry = $this->entry_repository->fetchEntryBySlug($new_slug);

        $this->entry_repository->updateTagsByEntry($entry, $new_tags);
        if ($success === true) {
            $this->response->redirect($this->router->generate('edit-entry', ['slug' => $new_slug]));
        }
    }

    /**
     * @param string $new_title
     *
     * @return string
     * @throws Exception
     */
    public function createSlugFromTitle(string $new_title): string
    {
        $max_slug_length = 30;
        $i = 2;
        $slug_filtered = $this->filterSlug($new_title, null);
        if ($slug_filtered === null) {
            return '';
        }

        // remove double dashes
        $slug_proposed = mb_ereg_replace('-+', '-', $slug_filtered);
        if ($slug_proposed === false) {
            return '';
        }

        // remove leading dashes
        $slug_proposed = mb_ereg_replace('^-+', '', $slug_proposed);
        if ($slug_proposed === false) {
            return '';
        }

        // truncate to X characters
        $slug_proposed = mb_substr($slug_proposed, 0, $max_slug_length);

        // remove trailing dashes
        $slug_proposed = mb_ereg_replace('-+$', '', $slug_proposed);
        if ($slug_proposed === false) {
            return '';
        }

        if ($slug_proposed === '') {
            return '';
        }
        $slug = $slug_proposed;
        while ($slug_not_available = $this->entry_repository->checkIfSlugExists($slug)) {
            $slug = substr($slug_proposed, 0, $max_slug_length - 1 - strlen((string)$i)) . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * @param array<string>     $error_fields
     * @param array<string>     $error_messages
     * @param string|null       $slug
     * @param string|null       $title
     * @param string|null       $url
     * @param string|null       $text
     * @param DateTimeInterface $datetime
     */
    public function editformErrorsFound(
        array $error_fields,
        array $error_messages,
        ?string $slug,
        ?string $title,
        ?string $url,
        ?string $text,
        ?DateTimeInterface $datetime
    ): void {
        $data = [];
        $data['slug'] = $slug;
        $data['title'] = $title;
        $data['url'] = $url;
        $data['text'] = $text;
        $data['date'] = $datetime;

        foreach ($error_fields as $field) {
            $data['error-' . $field] = true;
            $data['error-message-' . $field] = $error_messages[$field] ?? '';
        }

        $html = $this->renderer->render('editentry', $data);
        $this->response->setContent($html);
    }
}
