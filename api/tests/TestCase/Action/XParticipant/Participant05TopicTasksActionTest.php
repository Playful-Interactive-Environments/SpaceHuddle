<?php

namespace App\Test\TestCase\Action\XParticipant;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserLoginAction
 */
class Participant05TopicTasksActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
    }

    protected ?string $topicId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTrait();
        $this->topicId = $this->getFirstTopicId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testParticipantTopicTasks(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/topic/$this->topicId/participant_tasks/"
        );

        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_ACCEPTED, $response->getStatusCode());
        $this->assertJsonContentType($response);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testParticipantTopicTasksValidation(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/topic/xxx/participant_tasks/"
        );

        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
