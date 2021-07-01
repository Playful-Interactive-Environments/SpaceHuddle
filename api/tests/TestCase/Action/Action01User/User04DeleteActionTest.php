<?php

namespace App\Test\TestCase\Action\Action01User;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserDeleteAction
 */
class User04DeleteActionTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteUser(): void
    {
        $tableRowCount = $this->getTableRowCount("user");
        $request = $this->createJsonRequest(
            "DELETE",
            "/user/"
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin", "string1234", false));

        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount-1, "user");

        // Check double delete
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
                            "message" => "The logged-in user no longer exists.",
                            "field" => "id",
                        ],
                    ],
                ],
            ],
            $response
        );
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteUserValidation(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/user/"
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin", "string1234", false));
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}
