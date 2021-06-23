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
    public function testGetAllSessions(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/session/$this->sessionId/topics/"
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
        $request = $this->createRequest("GET", "/session/$this->sessionId/topics/");
        $request = $this->withJwtAuth($request)->withoutHeader("Authorization");
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}

