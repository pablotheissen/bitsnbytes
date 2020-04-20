<?php

declare(strict_types=1);


namespace Tests\Helpers;

use Bitsnbytes\Helpers\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{

    public function testGet()
    {
        $config = new Configuration(
            [
                'test' => 'TEST',
                'int' => 15,
                'array' => [
                    'level1' => 'ABCDEF',
                    'nested' => [
                        'level2' => 'GHIJKL',
                    ],
                ],
            ]
        );

        $this->assertSame('TEST', $config->get('test'));
        $this->assertSame(15, $config->get('int'));
        $this->assertSame('ABCDEF', $config->get('array.level1'));
        $this->assertSame('GHIJKL', $config->get('array.nested.level2'));
        $this->assertNull($config->get('unknown'));
        $this->assertSame('MNOPQR', $config->get('unknown', 'MNOPQR'));
        $this->assertSame(['level2' => 'GHIJKL'], $config->get('array.nested'));
    }
}
