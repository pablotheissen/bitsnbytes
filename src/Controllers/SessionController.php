<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionController extends Controller
{
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = [];

        $this->twig->render($response, 'login.twig', $data);
        return $response;
    }
}