<?php

declare(strict_types=1);

namespace Bitsnbytes\Helpers\Template;

interface RendererInterface
{
    /**
     * @param string      $template
     * @param array<mixed> $data
     *
     * @return string
     */
    public function render(string $template, array $data = null): string;
}
