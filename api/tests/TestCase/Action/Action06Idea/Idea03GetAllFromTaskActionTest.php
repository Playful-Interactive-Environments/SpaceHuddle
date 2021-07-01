<?php

namespace App\Test\TestCase\Action\Action06Idea;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Idea\IdeaReadAllFromTaskAction
 */
class Idea03GetAllFromTaskActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
        AppTestTrait::setUp as private setUpAppTrait;
    }

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
    public function testGetAllIdeasFromTask(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/task/$this->taskId/ideas/"
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
    public function testGetAllIdeasFromTaskWithoutLogin(): void
    {
        $request = $this->createRequest("GET", "/task/$this->taskId/ideas/");
        $request = $this->withJwtAuth($request)->withoutHeader("Authorization");
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}
