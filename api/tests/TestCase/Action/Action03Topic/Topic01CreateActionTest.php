<?php

namespace App\Test\TestCase\Action\Action03Topic;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Topic\TopicCreateAction
 */
class Topic01CreateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $sessionId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->sessionId = $this->getFirstSessionId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateTopic(): void
    {
        $tableRowCount = $this->getTableRowCount("topic");
        $request = $this->createJsonRequest(
            "POST",
            "/session/$this->sessionId/topic/",
            [
                "title" => "php unit test topic",
                "description" => "create from unit test"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "topic");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateTopicValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/session/$this->sessionId/topic/",
            [
                "description" => "create from unit test"
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
                            "message" => "This field is required",
                            "field" => "title",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
