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
class User01RegisterActionTest extends TestCase
{
    use AppTestTrait;
    use DatabaseTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateUser(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/user/register/",
            [
                "username" => "admin",
                "password" => "secret123",
                "passwordConfirmation" => "secret123"
            ]
        );
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount(2, "user");
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
