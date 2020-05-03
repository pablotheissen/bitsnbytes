<?php

declare(strict_types=1);


namespace Bitsnbytes\Helpers;

/**
 * Class Configuration
 *
 * Usage:
 *   $conig = new \Configuration([
 *      'db' => [
 *          'host' => 'localhost',
 *      ],
 *      'timezone' => 'Europe/Berlin',
 *   ]);
 *   $config->get('db.host'); // 'localhost'
 *   $config->get('timezone'); // 'Europe/Berlin'
 *   $config->timezone; // 'Europe/Berlin' –– does not work for arrays
 *
 * @package Bitsnbytes\Helpers
 */
final class Configuration
{
    /** @var array<mixed> $data */
    private array $data;

    /**
     * Configuration constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $name
     *
     * @return array|mixed|null
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * @param string $path Key of configuration variable. Separate nested arrays with dots, e.g. get('db.host') for
     *                     retrieving 'db'=>['host'=>'...']
     * @param mixed $default [optional] Default return value, <b>NULL</b> if empty
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
}