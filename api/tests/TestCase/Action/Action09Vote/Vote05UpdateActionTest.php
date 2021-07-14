<?php

namespace App\Test\TestCase\Action\Action09Vote;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Vote\VoteUpdateAction
 */
class Vote05UpdateActionTest extends TestCase
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
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->voteId = $this->getFirstVoteId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateVote(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/vote/",
            [
                "id" => $this->voteId,
                "detailRating" => 2.8
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
    public function testUpdateVoteInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/vote/",
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
