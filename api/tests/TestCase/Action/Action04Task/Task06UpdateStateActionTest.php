<?php

namespace App\Test\TestCase\Action\Action04Task;

use App\Domain\Task\Type\TaskState;
use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Task\TaskStateUpdateAction
 */
class Task06UpdateStateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $taskId;
    protected ?string $taskIdVote;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->taskId = $this->getFirstCategorisationTaskId();
        $this->taskIdVote = $this->getFirstVotingTaskId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateTaskState(): void
    {
        $state = TaskState::ACTIVE;
        $request = $this->createJsonRequest(
            "PUT",
            "/task/$this->taskId/client_application_state/$state/"
        );

        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        $request = $this->createJsonRequest(
            "PUT",
            "/task/$this->taskIdVote/client_application_state/$state/"
        );

        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateTaskStateInvalidId(): void
    {
        $state = TaskState::ACTIVE;
        $request = $this->createJsonRequest(
            "PUT",
            "/task/xxx/client_application_state/$state/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
