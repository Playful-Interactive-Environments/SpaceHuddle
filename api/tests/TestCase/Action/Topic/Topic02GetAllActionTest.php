<?php

namespace App\Test\TestCase\Action\Topic;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Topic\TopicReadAllAction
 */
class Topic02GetAllActionTest extends TestCase
{
    use AppTestTrait;
    use UserTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testGetAllSessions(): void
    {
        $sessionId = $this->getFirstSessionId();
        $request = $this->createJsonRequest(
            "GET",
            "/session/$sessionId/topics/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertJsonContentType($response);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGetAllSessionsWithoutLogin(): void
    {
        $sessionId = $this->getFirstSessionId();
        $request = $this->createRequest("GET", "/session/$sessionId/topics/");
        $request = $this->withJwtAuth($request)->withoutHeader("Authorization");
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}

