<?php

namespace App\Test\TestCase\Action\Action11SessionRole;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\SessionRole\SessionRoleUpdateAction
 */
class SessionRole04UpdateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $sessionId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->sessionId = $this->getFirstSessionId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateSessionRole(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/session/$this->sessionId/authorized_user/",
            [
                "username" => "test.facilitator@fhooe.at",
                "role" => "MODERATOR"
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
    public function testUpdateSessionRoleInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/session/$this->sessionId/authorized_user/",
            [
                "username" => "xxx",
                "role" => "MODERATOR"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertJsonContentType($response);
        $this->assertJsonData(
            [
                "error" => [
                    "message" => "Please check your input",
                    "code" => 422,
                    "details" => [
                        0 => [
                            "message" => "NotExist: Username not exists.",
                            "field" => "username",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
