<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;


use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ErrorController extends Controller
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function error404(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->error($response, 404, 'Sorry, I can’t seem to find what you’re looking for.');
    }

    /**
     * @param ResponseInterface $response
     * @param int               $code
     * @param string            $message
     *
     * @return ResponseInterface
     */
    private function error(ResponseInterface $response, int $code, string $message = ''): ResponseInterface
    {
        $data = [];

        $data['heading'] = sprintf('Error %d', $code);
        $data['message'] = $message;
        $data['url_newentry'] = $this->route_parser->urlFor('new-entry');

        try {
            $this->twig->render($response, 'error.twig', $data);
        } catch (Exception $exception) {
            $response = new Response();
            $response->getBody()->write("There was an internal error while building this page. Please inform the site admin.");
            $code = 500; // Internal Server Error
            // TODO Log error
        }
        return $response->withStatus($code);
    }
}