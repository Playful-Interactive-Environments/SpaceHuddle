<?php

namespace App\Test\TestCase\Action\Home;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\DatabaseCleanupTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 */
class HomeActionTest extends TestCase
{
    use AppTestTrait, DatabaseCleanupTestTrait {
        DatabaseCleanupTestTrait::dropTables insteadof AppTestTrait;
        AppTestTrait::setUp as private setUpAppTraid;
    }

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTraid();
        $this->cleanup();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAction(): void
    {
        $request = $this->createRequest("GET", "/");
        $response = $this->app->handle($request);

        // Assert: Redirect
        $this->assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
    }

    /**
     * Test invalid link.
     *
     * @return void
     */
    public function testPageNotFound(): void
    {
        $request = $this->createRequest("GET", "/nada");
        $response = $this->app->handle($request);

        // Assert: Not found
        $this->assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());
    }
}
