<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorController extends Controller
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function showError404(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->error404($response);
    }
}