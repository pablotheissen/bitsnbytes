<?php

declare(strict_types=1);

namespace Tests;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Uri;
use Tests\Application\Middleware\DatabaseMiddlewareTesting;

require_once __DIR__ . '/../src/Helper.php';

class TestCase extends PHPUnit_TestCase
{

}