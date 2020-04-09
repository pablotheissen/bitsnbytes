<?php

declare(strict_types=1);

namespace Bitsbytes\Template;

use ArrayAccess;

interface Renderer
{
    /**
     * @param string      $template
     * @param array<mixed> $data
     *
     * @return string
     */
    public function render(string $template, array $data = null): string;
}
