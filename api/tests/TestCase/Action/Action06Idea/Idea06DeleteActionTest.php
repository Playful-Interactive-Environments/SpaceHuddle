<?php

namespace App\Test\TestCase\Action\Action06Idea;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Idea\IdeaDeleteAction
 */
class Idea06DeleteActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
        AppTestTrait::setUp as private setUpAppTrait;
    }

    protected ?string $ideaId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->ideaId = $this->getFirstIdeaId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteIdea(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/idea/$this->ideaId/"
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
    public function testDeleteIdeaInvalidId(): void
    {
        $request = $this->createRequest("DELETE", "/idea/xxx/");
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
