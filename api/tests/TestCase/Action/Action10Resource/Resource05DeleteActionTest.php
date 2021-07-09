<?php

namespace App\Test\TestCase\Action\Action10Resource;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Resource\ResourceDeleteAction
 */
class Resource05DeleteActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $resourceId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTrait();
        $this->resourceId = $this->getFirstResourceId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteResource(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/resource/$this->resourceId/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteResourceInvalidId(): void
    {
        $request = $this->createRequest("DELETE", "/resource/xxx/");
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}

