<?php

declare(strict_types=1);

if (!function_exists('mb_startswith')) {
    function mb_startswith(string $haystack, string $needle): bool
    {
        $length = mb_strlen($needle);
        return (mb_substr($haystack, 0, $length) === $needle);
    }
}

if (!function_exists('mb_endswith')) {
    function mb_endswith(string $haystack, string $needle): bool
    {
        $length = mb_strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (mb_substr($haystack, -$length) === $needle);
    }
}