<?php

namespace App\Test\TestCase\Action\Action09Vote;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Vote\VoteReadAllAction
 */
class Vote02GetAllActionTest extends TestCase
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
        $this->taskId = $this->getFirstVotingTaskId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGetAllVotes(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/task/$this->taskId/votes/"
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
    public function testGetAllVotesWithoutLogin(): void
    {
        $request = $this->createRequest("GET", "/task/$this->taskId/votes/");
        $request = $this->withJwtAuth($request)->withoutHeader("Authorization");
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}

