<?php

namespace App\Test\TestCase\Action\Action11SessionRole;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\SessionRole\SessionRoleCreateAction
 */
class SessionRole01CreateActionTest extends TestCase
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
    public function testCreateSessionRole(): void
    {
        $tableRowCount = $this->getTableRowCount("session_role");
        $this->getAccessToken("test.facilitator@fhooe.at", "Secret123!", true);
        $request = $this->createJsonRequest(
            "POST",
            "/session/$this->sessionId/authorized_user/",
            [
                "username" => "test.facilitator@fhooe.at",
                "role" => "FACILITATOR"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "session_role");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateSessionRoleValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/session/$this->sessionId/authorized_user/",
            [
                "role" => "xxx"
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
                            "message" => "Required: This field is required",
                            "field" => "username",
                        ],
                        1 => [
                            "message" => "Type: Wrong session role type.",
                            "field" => "role",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
