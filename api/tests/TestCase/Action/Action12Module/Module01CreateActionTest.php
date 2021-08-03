<?php

namespace App\Test\TestCase\Action\Action12Module;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Module\ModuleCreateAction
 */
class Module01CreateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $taskId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->taskId = $this->getFirstTaskId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateModule(): void
    {
        $tableRowCount = $this->getTableRowCount("module");
        $request = $this->createJsonRequest(
            "POST",
            "/task/$this->taskId/module/",
            [
                "name" => "php unit test module"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "module");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateModuleValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/task/$this->taskId/module/",
            [
                "order" => 2
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
                            "field" => "name",
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
