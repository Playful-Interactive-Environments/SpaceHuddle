<?php

namespace App\Test\TestCase\Action\Action09Vote;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Vote\VoteCreateAction
 */
class Vote01CreateActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
        AppTestTrait::setUp as private setUpAppTrait;
    }

    protected ?string $taskId;
    protected ?string $ideaId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->taskId = $this->getFirstVotingTaskId();
        $this->ideaId = $this->getFirstIdeaId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateVote(): void
    {
        $tableRowCount = $this->getTableRowCount("vote");
        $request = $this->createJsonRequest(
            "POST",
            "/task/$this->taskId/vote/",
            [
                "ideaId" => $this->ideaId,
                "rating" => "2",
                "detailRating" => "2"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "vote");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateVoteValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/task/$this->taskId/vote/",
            [
                "ideaId" => "xxx",
                "rating" => "2",
                "detailRating" => "2"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertJsonContentType($response);
        $this->assertJsonData(
            [
                "error" => [
                    "message" => "Please check your input",
                    "code" => 422,
                    "details" => [
                        0 => [
                            "message" =>
                                "IdeaId is not a valid idea keys or do not belong to the same topic as the task.",
                            "field" => "ideaId",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
