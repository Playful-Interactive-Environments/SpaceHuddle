<?php

namespace App\Test\TestCase\Action\Action06Idea;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Idea\IdeaCreateForTopicAction
 */
class Idea01CreateForTopicActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
        AppTestTrait::setUp as private setUpAppTrait;
    }

    protected ?string $topicId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->topicId = $this->getFirstTopicId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateIdeaForTopic(): void
    {
        $tableRowCount = $this->getTableRowCount("idea");
        $request = $this->createJsonRequest(
            "POST",
            "/topic/$this->topicId/idea/",
            [
                "keywords" => "php unit test idea"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "idea");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateIdeaForTopicValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/topic/$this->topicId/idea/",
            [
                "description" => "php unit test idea"
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
                            "message" => "This field is required",
                            "field" => "keywords"
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
