<?php

namespace App\Test\TestCase\Action\User;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserChangePasswordAction
 */
class User03UpdateActionTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateUser(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/user/",
            [
                "oldPassword" => "secret123",
                "password" => "string1234",
                "passwordConfirmation" => "string1234"
            ]
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin", "secret123", false));

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
    public function testUpdateUserValidation(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/user/",
            [
                "oldPassword" => "secret123",
                "password" => "secret1233",
                "passwordConfirmation" => "secret123"
            ]
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin", "string1234", false));
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
                            "message" => "Password and confirmation do not match.",
                            "field" => "passwordConfirmation",
                        ],
                    ],
                ],
            ],
            $response
        );
    }
}
