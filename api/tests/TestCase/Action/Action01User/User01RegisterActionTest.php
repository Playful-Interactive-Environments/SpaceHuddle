<?php

namespace App\Test\TestCase\Action\Action01User;

use App\Test\Traits\AppTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\User\UserRegisterAction
 */
class User01RegisterActionTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateUser(): void
    {
        $tableRowCount = $this->getTableRowCount("user");
        $request = $this->createJsonRequest(
            "POST",
            "/user/register/",
            [
                "username" => "admin@fhooe.at",
                "password" => "Secret123!",
                "passwordConfirmation" => "Secret123!"
            ]
        );
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "user");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateUserValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/user/register/",
            [
                "username" => "",
                "password" => "1234",
                "passwordConfirmation" => "secret123"
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
                            "message" => "This field cannot be left empty",
                            "field" => "username",
                        ],
                        1 => [
                            "message" => "Too short",
                            "field" => "password",
                        ],
                        2 => [
                            "message" => "Password must contain at least one lowercase and uppercase letter, a number and a special character.",
                            "field" => "password",
                        ],
                        3 => [
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
