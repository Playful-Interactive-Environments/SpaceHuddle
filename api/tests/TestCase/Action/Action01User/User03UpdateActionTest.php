<?php

namespace App\Test\TestCase\Action\Action01User;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

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
                "oldPassword" => "Secret123!",
                "password" => "String1234!",
                "passwordConfirmation" => "String1234!"
            ]
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin@fhooe.at", "Secret123!", false));

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
                "oldPassword" => "Secret123!",
                "password" => "Secret1233!",
                "passwordConfirmation" => "Secret123"
            ]
        );
        $request = $this->withJwtAuth($request, $this->getAccessToken("admin@fhooe.at", "String1234!", false));
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
                            "message" => "Comparison: Password and confirmation do not match.",
                            "field" => "passwordConfirmation",
                        ],
                    ],
                ],
            ],
            $response
        );
    }
}
