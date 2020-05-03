<?php

declare(strict_types=1);

namespace Bitsnbytes\Controllers;

use Bitsnbytes\Models\Entry\Entry;
use Bitsnbytes\Models\Entry\EntryNotFoundException;
use Bitsnbytes\Models\Entry\EntryRepository;
use Bitsnbytes\Models\Tag\TagNotFoundException;
use Bitsnbytes\Models\Tag\TagRepository;
use DateTimeInterface;
use Erusev\Parsedown\Parsedown;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


class EntryController extends Controller
{
    private EntryRepository $entry_repository;
    private TagRepository $tag_repository;

    public function __construct(
        EntryRepository $entry_repository,
        TagRepository $tag_repository,
        Parsedown $parsedown,
        Twig $twig,
        RouteParserInterface $route_parser
    ) {
        parent::__construct($parsedown, $twig, $route_parser);
        $this->entry_repository = $entry_repository;
        $this->tag_repository = $tag_repository;
    }

    /**
     * @param Request       $request
     * @param Response      $response
     * @param array<string> $args
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function showBySlug(Request $request, Response $response, array $args = []): Response
    {
        try {
            $entry = $this->entry_repository->fetchEntryBySlug($args['slug']);
        } catch (EntryNotFoundException $e) {
            $response->getBody()->write('404 - Page not found');
            return $response->withStatus(404);
        }

        $data = ['entry' => $entry->toArray()];
        $data['entry']['text'] = $this->parsedown->toHtml($entry->text ?? '');
        $data['entry']['url_edit'] = $this->route_parser->urlFor('edit-entry', ['slug' => $entry->slug]);
        $data['entry']['date_formatted'] = $entry->date->format('d.m.Y'); // TODO: use config date

        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        $this->twig->render($response, 'Entry.html', $data);
        return $response;
    }

    /**
     * @param Request       $request
     * @param Response      $response
     * @param array<string> $args
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function showByTag(Request $request, Response $response, array $args = []): Response
    {
        try {
            $tag = $this->tag_repository->fetchTagBySlug($args['tag']);
        } catch (TagNotFoundException $e) {
            $response->getBody()->write('404 - Page not found');
            return $response->withStatus(404);
        }

        $entries = $this->entry_repository->fetchEntriesByTag($tag, true);
        array_walk(
            $entries,
            function (&$entry): void {
                $entry['url_edit'] = $this->route_parser->urlFor(
                    'edit-entry',
                    ['slug' => $entry['slug']]
                );
                $entry['text'] = $this->parsedown->toHtml($entry['text']);
                $entry['date_formatted'] = $entry['date']->format('d.m.Y'); // TODO: use config date
            }
        );

        $data = ['entries' => $entries, 'heading' => 'Entries tagged as ›' . $tag->title . '‹'];

        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        $this->twig->render($response, 'entrylist.html', $data);
        return $response;
    }

    /**
     * @param Request       $request
     * @param Response      $response
     * @param array<string> $args
     *
     * @return Response
     */
    public function showLatest(Request $request, Response $response, array $args = []): Response
    {
        $data = [];

        $entries = $this->entry_repository->fetchLatestEntries(true);
        array_walk(
            $entries,
            function (&$entry): void {
                $entry['url_edit'] = $this->route_parser->urlFor(
                    'edit-entry',
                    ['slug' => $entry['slug']]
                );
                $entry['text'] = $this->parsedown->toHtml($entry['text']);
                $entry['date_formatted'] = $entry['date']->format('d.m.Y'); // TODO: use config date
            }
        );
        $data['entries'] = $entries;

        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        $this->twig->render($response, 'entrylist.html', $data);
        return $response;
    }

    /**
     * @param Request       $request
     * @param Response      $response
     * @param array<string> $args
     *
     * @return Response
     * @throws Exception
     */
    public function editformBySlug(Request $request, Response $response, array $args = []): Response
    {
        try {
            $entry = $this->entry_repository->fetchEntryBySlug($args['slug']);
        } catch (EntryNotFoundException $e) {
            $response->getBody()->write('404 - Page not found');
            return $response->withStatus(404);
        }

        $data = $entry->toArray();
        $data['date_atom_date'] = $entry->date->format('Y-m-d');
        $data['date_atom_time'] = $entry->date->format('H:i');

        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        $this->twig->render($response, 'editentry.html', $data);
        return $response;
    }

    /**
     * @param Request       $request
     * @param Response      $response
     * @param array<string> $args
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function newform(Request $request, Response $response, array $args = []): Response
    {
        $data = [];
        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        $this->twig->render($response, 'editentry.html', $data);
        return $response;
    }

    /**
     * @param Request       $request
     * @param Response      $response
     * @param array<string> $args
     *
     * @return Response
     * @throws EntryNotFoundException
     */
    public function saveEntry(Request $request, Response $response, array $args = []): Response
    {
        $error_fields = [];
        $error_messages = [];

        $data = (array)$request->getParsedBody();

        $new_title = $data['title'];
        if ($new_title === '' || $new_title === null) {
            $error_fields[] = 'title';
            $error_messages['title'] = 'Entry must have a title.';
        }
        $user_slug = $data['slug'];
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
        $new_url = $this->filterUrl($data['url']);
        $new_text = $data['text'];
        $new_date = $this->filterDate($data['date']);
        if ($new_date === null) {
            $error_fields[] = 'date';
        } elseif ($new_date === '') {
            $new_date = date('Y-m-d');
        }
        $new_time = $this->filterTime($data['time']);
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
        $new_tags = array_filter($data['tags']);

        if (count($error_fields) > 0) {
            return $this->editformErrorsFound(
                $response,
                array_unique($error_fields),
                $error_messages,
                $user_slug,
                $new_title,
                $new_url,
                $new_text,
                $new_datetime
            );
        }

        $entry = new Entry(null, $new_title, $new_slug, $new_url, $new_text, $new_datetime);

        if (isset($args['slug'])) { // UPDATE existing entry
            // TODO catch exception when error during update occurs
            $success = $this->entry_repository->updateBySlug($args['slug'], $entry);
        } else { // NEW entry
            $success = $this->entry_repository->createNewEntry($entry);
        }

        // Fetch entry id from database by simply fetching the entire entry again
        $entry = $this->entry_repository->fetchEntryBySlug($new_slug);

        $this->entry_repository->updateTagsByEntry($entry, $new_tags);
        if ($success === true) {
            $redirect_to = $this->route_parser->urlFor('edit-entry', ['slug' => $new_slug]);
            return $response->withHeader('Location', $redirect_to);
        }
        return $response;
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
        ResponseInterface $response,
        array $error_fields,
        array $error_messages,
        ?string $slug,
        ?string $title,
        ?string $url,
        ?string $text,
        ?DateTimeInterface $datetime
    ): Response {
        $data = [];
        $data['slug'] = $slug;
        $data['title'] = $title;
        $data['url'] = $url;
        $data['text'] = $text;
        $data['date'] = $datetime;
        $data['date_atom_date'] = $datetime->format('Y-m-d');
        $data['date_atom_time'] = $datetime->format('H:i');

        foreach ($error_fields as $field) {
            $data['error_' . $field] = true;
            $data['error_message_' . $field] = $error_messages[$field] ?? '';
        }
        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        $this->twig->render($response, 'editentry.html', $data);
        return $response;
    }
}
