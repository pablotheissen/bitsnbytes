<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;


use Bitsnbytes\Models\Update\Update;
use Erusev\Parsedown\Parsedown;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

class UpdateController extends Controller
{
    private Update $update;

    public function __construct(Update $update, Parsedown $parsedown, Twig $twig, RouteParserInterface $route_parser)
    {
        parent::__construct($parsedown, $twig, $route_parser);
        $this->update = $update;
    }

    public function update(Request $request, Response $response): Response
    {
        $content = '';

        $content .= "### Cleanup tmp folder\n";
        $content .= "Deleted files:\n";
        $deleted_files = $this->update->cleanupTmpFolder();
        foreach ($deleted_files as $filename) {
            $truncate_path = strpos($filename, 'tmp');
            if($truncate_path === false) {
                $truncate_path = 0;
            }
            $content .= "- " . substr($filename, $truncate_path) . "\n";
        }

        $html = $this->parsedown->toHtml($content);

        // TODO: use more fitting template
        $this->twig->render($response, 'error.twig', ['heading' => 'Update', 'message' => $html]);
        return $response;
    }
}