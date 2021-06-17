<?php


namespace App\Test\TestCase\Action\Session;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Session\SessionCreateAction
 */
class Session05DeleteActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTraid;
    }
    use UserTestTrait;

    protected ?string $sessionId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTraid();
        $this->sessionId = $this->getFirstSessionId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteSessions(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/session/$this->sessionId/"
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
    public function testDeleteSessionsInvalidId(): void
    {
        $request = $this->createRequest("DELETE", "/session/xxx/");
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
