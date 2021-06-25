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
class Participant07DeleteActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteParticipant(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/participant/"
        );

        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_ACCEPTED, $response->getStatusCode());
        $this->assertJsonContentType($response);
    }
}
