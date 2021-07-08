<?php

namespace App\Test\TestCase\Action\Action09Vote;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Vote\VoteDeleteAction
 */
class Vote06DeleteActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $voteId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTrait();
        $this->voteId = $this->getFirstVoteId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteVote(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/vote/$this->voteId/"
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
    public function testDeleteVoteInvalidId(): void
    {
        $request = $this->createRequest("DELETE", "/vote/xxx/");
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}

