<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;

use Bitsnbytes\Models\Remote\Remote;
use Erusev\Parsedown\Parsedown;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

class RemoteController extends Controller
{
    /**
     * @var Remote
     */
    private Remote $remote;

    public function __construct(Remote $remote, Parsedown $parsedown, Twig $twig, RouteParserInterface $route_parser)
    {
        parent::__construct($parsedown, $twig, $route_parser);
        $this->remote = $remote;
    }

    /**
     * Fetch title and description from a remote website and return these values as a json object. Returns empty
     * strings if there were any problems (website not reachable, missing title tag, etc.).
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function fetchTitleAndDescription(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $return_data = [
            'title' => '',
            'description' => ''
        ];
        if (!array_key_exists('url', $params)) {
            return $this->returnJsonResponse($return_data, $response);
        }

        $url_filtered = filter_var($params['url'], FILTER_VALIDATE_URL);
        if ($url_filtered === false) {
            return $this->returnJsonResponse($return_data, $response);
        }

        list($title, $description) = $this->remote->fetchTitleAndDescription($url_filtered, '', '');
        $return_data['title'] = $title;
        $return_data['description'] = $description;

        return $this->returnJsonResponse($return_data, $response);
    }

}