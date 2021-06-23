<?php

namespace App\Test\TestCase\Action\Session;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Session\SessionCreateAction
 */
class Session01CreateActionTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateSession(): void
    {
        $tableRowCount = $this->getTableRowCount("session");
        $request = $this->createJsonRequest(
            "POST",
            "/session/",
            [
                "title" => "php unit test session",
                "description" => "create from unit test",
                "maxParticipants" => 100,
                "expirationDate" => "2022-01-01"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "session");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateSessionValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/session/",
            [
                "maxParticipants" => 100,
                "expirationDate" => "2022-01-01"
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
                            "field" => "title",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
