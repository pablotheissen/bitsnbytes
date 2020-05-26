<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;


use Bitsnbytes\Models\Entry\Entry;
use Bitsnbytes\Models\Entry\EntryRepository;
use Bitsnbytes\Models\Tag\TagRepository;
use Erusev\Parsedown\Parsedown;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

class SearchController extends Controller
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
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function search(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = $params['q'] ?? null;

        $tags = $this->tag_repository->findTagsBySearchString($query);

        $entries_by_title = $this->entry_repository->findEntriesMatchingTags($query, false);
//        $entries_by_content = $this->entry_repository->fetchEntriesByTags($tags, true, false);
        $entries_by_tags = $this->entry_repository->fetchEntriesByTags($tags, true, false);

        $merged_results = array_unique(array_merge($entries_by_title/*, $entries_by_content*/, $entries_by_tags));

        $entries = [];
        foreach ($merged_results as $entry) {
            /** @var Entry $entry */
            $entries[] = $entry->toArray();
        }

//        $entries = $this->entry_repository->fetchEntriesByTag($tag, true);
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

        $data = ['entries' => $entries, 'heading' => 'Search results for ›' . $query . '‹'];

        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');
        $data['search_query'] = $params['q'];

        $this->twig->render($response, 'entrylist.twig', $data);
        return $response;
    }
}