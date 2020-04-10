<?php

declare(strict_types=1);

namespace Bitsbytes\Template;

use AltoRouter;
use Exception;
use Mustache_Engine;

class MustacheRenderer implements Renderer
{

    private Mustache_Engine $engine;
    private AltoRouter $router;

    public function __construct(
        Mustache_Engine $engine,
        AltoRouter $router
    ) {
        $this->engine = $engine;
        $this->router = $router;
    }

    /**
     * @param string       $template
     * @param array<mixed> $data
     *
     * @return string
     * @throws Exception
     */
    public function render(string $template, array $data = null): string
    {
        if ($data === null) {
            $data = [];
        }

        $data['url-newentry'] = $this->router->generate('new-entry');

        return $this->engine->render($template, $data);
    }
}
