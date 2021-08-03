<?php

namespace App\Test\TestCase\Action\Action12Module;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Module\ModuleUpdateAction
 */
class Module04UpdateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $moduleId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->moduleId = $this->getFirstModuleId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateModule(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/module/",
            [
                "id" => $this->moduleId,
                "order" => 2
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
    public function testUpdateModuleInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/module/",
            [
                "id" => "xxx",
                "order" => 2
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
