<?php

namespace App\Test\TestCase\Action\Action04Task;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Task\TaskCreateAction
 */
class Task01CreateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $topicId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->topicId = $this->getFirstTopicId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateTask(): void
    {
        $tableRowCount = $this->getTableRowCount("task");
        $request = $this->createJsonRequest(
            "POST",
            "/topic/$this->topicId/task/",
            [
                "taskType" => "brainstorming",
                "name" => "php unit test task"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "task");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateTaskValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/topic/$this->topicId/task/",
            [
                "taskType" => "xxx",
                "name" => "php unit test task"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertJsonContentType($response);
        $this->assertJsonData(
            [
                "error" => [
                    "message" => "Please check your input",
                    "code" => 422,
                    "details" => [
                        0 => [
                            "message" => "Wrong task type.",
                            "field" => "taskType",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
