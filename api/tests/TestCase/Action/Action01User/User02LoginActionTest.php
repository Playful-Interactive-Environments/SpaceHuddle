<?php

namespace App\Test\TestCase\Action\Action01User;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserLoginAction
 */
class User02LoginActionTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testLoginUser(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/user/login/",
            [
                "username" => "admin",
                "password" => "secret123"
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
            "/user/login/",
            [
                "username" => "admin",
                "password" => "1234",
            ]
        );

        $response = $this->app->handle($request);
        #echo $response->getBody();
        #echo "\n\n";

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
                            "message" => "Username or password wrong.",
                            "field" => "username or password",
                        ],
                    ],
                ],
            ],
            $response
        );
    }
}
