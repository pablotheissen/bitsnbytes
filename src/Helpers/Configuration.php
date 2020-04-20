<?php

declare(strict_types=1);


namespace Bitsnbytes\Helpers;


final class Configuration
{
    /** @var array<mixed> $data */
    private array $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $path
     * @param ?mixed   $default
     *
     * @return mixed
     *
     * @see   https://github.com/selective-php/config/blob/34fc2a349c9a225497f5bfc6e59da97233936be9/src/Configuration.php#L284
     */
    public function get(string $path, $default = null)
    {
        if (array_key_exists($path, $this->data)) {
            return $this->data[$path];
        }

        if (strpos($path, '.') === false) {
            return $default;
        }

        $pathKeys = explode('.', $path);

        $arrayCopyOrValue = $this->data;

        foreach ($pathKeys as $pathKey) {
            if (!isset($arrayCopyOrValue[$pathKey])) {
                return $default;
            }
            $arrayCopyOrValue = $arrayCopyOrValue[$pathKey];
        }

        return $arrayCopyOrValue;
    }

    public function __get($name)
    {
        var_dump($name);
        return $this->get($name);
    }
}