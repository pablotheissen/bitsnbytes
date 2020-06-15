<?php

declare(strict_types=1);


namespace Bitsnbytes\Controllers;


use Bitsnbytes\Helpers\AuthManager;
use Erusev\Parsedown\Parsedown;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

class SessionController extends Controller
{
    private AuthManager $auth_manager;

    public function __construct(
        Parsedown $parsedown,
        Twig $twig,
        RouteParserInterface $route_parser,
        AuthManager $auth_manager
    ) {
        parent::__construct($parsedown, $twig, $route_parser);
        $this->auth_manager = $auth_manager;
    }

    public function loginForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = [];

        $this->twig->render($response, 'login.twig', $data);
        return $response;
    }

    public function checkCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();

        // TODO: check if username and password are not empty
        $login_successful = $this->auth_manager->tryLogin($data['username'], $data['password']);

        if ($login_successful === true) {
            return $this->redirectToRoute($response, 'home');
        }

        $data['error_username'] = true;
        $data['error_password'] = true;
        $data['error_message_username'] = 'Username or password incorrect.';

        $this->twig->render($response, 'login.twig', $data);

        return $response;
    }
}