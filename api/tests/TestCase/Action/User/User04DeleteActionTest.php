<?php

namespace App\Test\TestCase\Action\User;

use App\Domain\User\Type\UserRoleType;
use App\Test\Traits\AppTestTrait;
use Cake\Chronos\Chronos;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserLoginAction
 */
class User04DeleteActionTest extends TestCase
{
    use AppTestTrait;
    use DatabaseTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteUser(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/user/"
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin", "secret123"));

        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount(1, "user");

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
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin", "secret123"));
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}
