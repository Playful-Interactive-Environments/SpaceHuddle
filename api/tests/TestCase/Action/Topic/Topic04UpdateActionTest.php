<?php

namespace App\Test\TestCase\Action\Topic;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Topic\TopicUpdateAction
 */
class Topic04UpdateActionTest extends TestCase
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
    public function testUpdateTopic(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/topic/",
            [
                "id" => $this->topicId,
                "description" => null
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
    public function testUpdateTopicInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/topic/",
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
