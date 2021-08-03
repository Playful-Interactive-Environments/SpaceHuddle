<?php

namespace App\Test\TestCase\Action\Action10Resource;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Resource\ResourceCreateAction
 */
class Resource01CreateActionTest extends TestCase
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
    public function testCreateResource(): void
    {
        $tableRowCount = $this->getTableRowCount("resource");
        $request = $this->createJsonRequest(
            "POST",
            "/session/$this->sessionId/resource/",
            [
                "title" => "php unit test resource"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "resource");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateResourceValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/session/$this->sessionId/resource/",
            [
                "link" => "create from unit resource"
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
                            "field" => "title",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
