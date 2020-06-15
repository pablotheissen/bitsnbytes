<?php

declare(strict_types=1);

namespace Bitsnbytes\Helpers;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private SessionManager $session_manager;

    public function __construct(SessionManager $session_manager)
    {
        $this->session_manager = $session_manager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        $this->session_manager->start();

        return $handler->handle($request);
    }
}
