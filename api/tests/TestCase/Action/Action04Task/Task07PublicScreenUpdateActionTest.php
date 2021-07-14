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
 * @coversDefaultClass \App\Action\Session\PublicScreenUpdateAction
 */
class Task07PublicScreenUpdateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $sessionId;
    protected ?string $taskId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->sessionId = $this->getFirstSessionId();
        $this->taskId = $this->getFirstBrainstormingTaskId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdatePublicScreen(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/session/$this->sessionId/public_screen/$this->taskId/"
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
    public function testUpdatePublicScreenInvalidId(): void
    {

        $request = $this->createJsonRequest(
            "PUT",
            "/session/xxx/public_screen/$this->taskId/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
