<?php

declare(strict_types=1);

namespace Bitsbytes\Template;

use ArrayAccess;
use Mustache_Engine;

class MustacheRenderer implements Renderer
{

    private Mustache_Engine $engine;

    public function __construct(Mustache_Engine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param string      $template
     * @param array<mixed> $data
     *
     * @return string
     */
    public function render(string $template, array $data = null): string
    {
        if ($data === null) {
            $data = [];
        }
        return $this->engine->render($template, $data);
    }
}
