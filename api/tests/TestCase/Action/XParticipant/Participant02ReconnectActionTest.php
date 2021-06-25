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
class Participant02ReconnectActionTest extends TestCase
{
    use AppTestTrait, ParticipantTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
        ParticipantTestTrait::getAccessToken insteadof AppTestTrait;
    }

    protected ?string $browserKey;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTrait();
        $this->browserKey = $this->getFirstBrowserKey();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testReconnectParticipant(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/participant/reconnect/$this->browserKey/"
        );

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
    public function testReconnectValidation(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/participant/reconnect/xxx/"
        );

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
                            "message" => "browserKey wrong.",
                            "field" => "browserKey",
                        ],
                    ],
                ],
            ],
            $response
        );
    }
}
