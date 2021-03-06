<?php

namespace App\Test\Traits;

use Selective\TestTrait\Traits\ArrayTestTrait;
use Selective\TestTrait\Traits\ContainerTestTrait;
use Selective\TestTrait\Traits\DatabaseTestTrait;
use Selective\TestTrait\Traits\HttpJsonTestTrait;
use Selective\TestTrait\Traits\HttpTestTrait;
use Selective\TestTrait\Traits\MockTestTrait;
use Selective\TestTrait\Traits\RouteTestTrait;
use Slim\App;

/**
 * App Test Trait.
 */
trait AppTestTrait
{
    use ArrayTestTrait;
    use ContainerTestTrait;
    use JwtAuthTestTrait;
    use HttpTestTrait;
    use HttpJsonTestTrait;
    use MockTestTrait;
    use RouteTestTrait;
    use DatabaseTestTrait;

    /**
     * @var App
     */
    protected $app;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->app = require __DIR__ . "/../../config/bootstrap.php";
        $this->setUpContainer($this->app->getContainer());

        #echo static::class . "\n";

        /*
        if (method_exists($this, "setUpDatabase")) {
            $this->setUpDatabase(__DIR__ . "/../../resources/schema/schema.sql");
        }
        */
    }
}
