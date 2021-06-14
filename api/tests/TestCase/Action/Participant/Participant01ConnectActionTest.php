<?php

namespace App\Test\TestCase\Action\Participant;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserLoginAction
 */
class Participant01ConnectActionTest extends TestCase
{
    use AppTestTrait;
    use DatabaseTestTrait;

    protected ?string $sessionKey;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setUpAppTraid();
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
                "ipHash" => "testIP"
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
    public function testLoginUserValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/participant/connect/",
            [
                "sessionKey" => "NoKey",
                "ipHash" => "testIP",
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
                            "message" => "sessionKey wrong.",
                            "field" => "sessionKey",
                        ],
                    ],
                ],
            ],
            $response
        );
    }
}
