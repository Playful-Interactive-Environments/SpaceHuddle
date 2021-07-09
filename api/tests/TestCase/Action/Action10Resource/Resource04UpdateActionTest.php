<?php

namespace App\Test\TestCase\Action\Action10Resource;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Resource\ResourceUpdateAction
 */
class Resource04UpdateActionTest extends TestCase
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
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->resourceId = $this->getFirstResourceId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateResource(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/resource/",
            [
                "id" => $this->resourceId,
                "link" => "www.pie-lab.at"
            ]
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
    public function testUpdateResourceInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/resource/",
            [
                "id" => "xxx",
                "link" => "www.pie-lab.at"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
