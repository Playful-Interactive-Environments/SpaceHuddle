<?php

namespace App\Test\TestCase\Action\Action04Task;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Task\TaskUpdateAction
 */
class Task05UpdateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $taskId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->taskId = $this->getFirstTaskId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateTask(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/task/",
            [
                "id" => $this->taskId,
                "description" => null,
                "state" => "ACTIVE"
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
    public function testUpdateTaskInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/task/",
            [
                "id" => "xxx",
                "description" => null
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
