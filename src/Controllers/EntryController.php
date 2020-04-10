<?php

declare(strict_types=1);

namespace Bitsbytes\Controllers;

use AltoRouter;
use Bitsbytes\Models\Entry;
use Bitsbytes\Models\EntryRepository;
use Bitsbytes\Template\Renderer;
use DateTimeInterface;
use Exception;
use Http\Request;
use Http\Response;

class EntryController extends Controller
{
    private EntryRepository $entryRepository;
    private AltoRouter $router;

    public function __construct(
        EntryRepository $entryRepository,
        AltoRouter $router,
        Request $request,
        Response $response,
        Renderer $renderer
    ) {
        parent::__construct($request, $response, $renderer);
        $this->entryRepository = $entryRepository;
        $this->router = $router;
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function showBySlug(array $params): void
    {
        $entry = $this->entryRepository->findEntryBySlug($params['slug']);
        $html = $this->renderer->render('Entry', $entry->toArray());
        $this->response->setContent($html);
    }

    /**
     * @param array<string> $params
     *
     * @throws Exception
     */
    public function showLatest(array $params): void
    {
        $entries = $this->entryRepository->findLatestEntries(true);
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
        $entry = $this->entryRepository->findEntryBySlug($params['slug']);
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
        // TODO differentiate between updated and new entry

        $error_fields = [];

        $new_title = $this->request->getBodyParameter('title');
        if (empty($new_title)) {
            $error_fields[] = 'title';
        }
        $user_slug = $this->request->getBodyParameter('slug');
        $new_slug = $this->filterSlug($user_slug);
        if (empty($user_slug)) {
            $new_slug = $this->createSlugFromTitle($new_title);
        } elseif ($user_slug != $new_slug) {
            $error_fields[] = 'slug';
        }
        $new_url = $this->filterUrl($this->request->getBodyParameter('url'));
        $new_text = $this->request->getBodyParameter('text');
        $new_date = $this->filterDate($this->request->getBodyParameter('date'));
        if ($new_date === null) {
            $error_fields[] = 'date';
        } elseif (empty($new_date)) {
            $new_date = date('Y-m-d');
        }
        $new_time = $this->filterTime($this->request->getBodyParameter('time'));
        if ($new_time === null) {
            $error_fields[] = 'time';
        } elseif (empty($new_time)) {
            $new_time = date('H:i:s');
        }
        $new_datetime = $this->createDateTimeFromDateAndTime($new_date, $new_time);
        if ($new_datetime === null) {
            $error_fields[] = 'date';
            $error_fields[] = 'time';
        }

        if (!empty($error_fields)) {
            $this->editformErrorsFound(
                array_unique($error_fields),
                $user_slug,
                $new_title,
                $new_url,
                $new_text,
                $new_datetime
            );
            return;
        }

        $entry = new Entry(-1, $new_title, $new_slug, $new_url, $new_text, $new_datetime);

        // TODO catch exception when error during update occurs
        $update_success = $this->entryRepository->updateBySlug($params['slug'], $entry);

        if ($update_success === true) {
            $this->response->redirect($this->router->generate('edit-entry', ['slug' => $new_slug]));
        }
    }

    /**
     * @param string $new_title
     *
     * @return string
     * @throws Exception
     */
    public function createSlugFromTitle(string $new_title): ?string
    {
        $max_slug_length = 30;
        $i = 2;
        $proposed_slug = $this->filterSlug($new_title);
        if ($proposed_slug === '') {
            return '';
        }
        $slug = $proposed_slug;
        while ($slug_not_available = $this->entryRepository->checkIfSlugExists($slug)) {
            $slug = substr($proposed_slug, 0, $max_slug_length - 1 - strlen((string)$i)) . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * @param array<string>     $error_fields
     * @param string|null       $slug
     * @param string|null       $title
     * @param string|null       $url
     * @param string|null       $text
     * @param DateTimeInterface $datetime
     */
    public function editformErrorsFound(
        array $error_fields,
        ?string $slug,
        ?string $title,
        ?string $url,
        ?string $text,
        ?DateTimeInterface $datetime
    ): void {
        $data['slug'] = $slug;
        $data['title'] = $title;
        $data['url'] = $url;
        $data['text'] = $text;
        $data['date'] = $datetime;

        foreach ($error_fields as $field) {
            $data['error-' . $field] = true;
        }

        $html = $this->renderer->render('editentry', $data);
        $this->response->setContent($html);
    }
}