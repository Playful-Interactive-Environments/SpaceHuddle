<?php

namespace App\Test\TestCase\Action\Action05Participant;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserLoginAction
 */
class Participant01ConnectActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $sessionKey;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTrait();
        $this->sessionKey = $this->getFirstSessionKey();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testConnectParticipant(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/participant/connect/",
            [
                "sessionKey" => $this->sessionKey,
                "ip" => "testIP"
            ]
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
    public function testConnectValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/participant/connect/",
            [
                "sessionKey" => "NoKey",
                "ip" => "testIP",
            ]
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
                            "message" => "NotExist: sessionKey wrong",
                            "field" => "sessionKey",
                        ],
                    ],
                ],
            ],
            $response
        );
    }
}
