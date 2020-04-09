<?php

declare(strict_types=1);

namespace Bitsbytes\Controllers;

use Bitsbytes\Models\EntryRepository;
use Bitsbytes\Template\Renderer;
use DateTimeInterface;
use Exception;
use Http\Request;
use Http\Response;

class EntryController extends Controller
{
    private EntryRepository $entryRepository;

    public function __construct(
        EntryRepository $entryRepository,
        Request $request,
        Response $response,
        Renderer $renderer
    ) {
        parent::__construct($request, $response, $renderer);
        $this->entryRepository = $entryRepository;
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
     *
     * @throws Exception
     */
    public function saveEntry(array $params): void
    {
        $error_fields = [];
        $user_slug = $this->request->getBodyParameter('slug');
        $new_slug = $this->filterSlug($user_slug);
        if ($user_slug != $new_slug) {
            $error_fields[] = 'slug';
        }
        $new_url = $this->filterUrl($this->request->getBodyParameter('url'));
        $new_title = $this->request->getBodyParameter('title');
        if (empty($new_title)) {
            $error_fields[] = 'title';
        }
        $new_text = $this->request->getBodyParameter('text');
        $new_date = $this->filterDate($this->request->getBodyParameter('date'));
        if ($new_date === null OR empty($new_date)) {
            $error_fields[] = 'date';
        }
        $new_time = $this->filterTime($this->request->getBodyParameter('time'));
        if ($new_time === null OR empty($new_time)) {
            $error_fields[] = 'time';
        }
        // TODO
        $new_datetime = new \DateTime("now");

        if (!empty($error_fields)) {
            $this->editformErrorsFound(
                $error_fields,
                $user_slug,
                $new_title,
                $new_url,
                $new_text,
                $new_datetime
            );
            return;
        }
//        var_dump($this->request->getBodyParameter('slug'));
//        var_dump($params);
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
        DateTimeInterface $datetime
    ): void {
        $data['slug'] = $slug;
        $data['slug'] = $title;
        $data['url'] = $url;
        $data['text'] = $text;
        $data['date'] = $datetime;
        $html = $this->renderer->render('editentry', $data);
        $this->response->setContent($html);
    }
}
