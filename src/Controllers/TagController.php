<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;

use Bitsnbytes\Models\Tag\TagRepository;
use Erusev\Parsedown\Parsedown;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

class TagController extends Controller
{
    private TagRepository $tag_repository;

    public function __construct(
        TagRepository $tag_repository,
        Parsedown $parsedown,
        Twig $twig,
        RouteParserInterface $route_parser
    ) {
        parent::__construct($parsedown, $twig, $route_parser);
        $this->tag_repository = $tag_repository;
    }

    public function searchTag(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $query = $params['q'] ?? null;

        if (is_null($query)) {
            return $response;
        }

        $results = $this->tag_repository->findTagsBySearchString($query);

        foreach ($results as $result) {
            $this->twig->render($response, 'partials/autocomplete-element.twig', ['title' => $result->title]);
        }

        return $response;
    }
}