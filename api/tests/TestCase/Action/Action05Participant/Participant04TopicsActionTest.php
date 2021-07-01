<?php

namespace App\Test\TestCase\Action\Action05Participant;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserLoginAction
 */
class Participant04TopicsActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testParticipantTopics(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/participant/topics/"
        );

        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_ACCEPTED, $response->getStatusCode());
        $this->assertJsonContentType($response);
    }
}
