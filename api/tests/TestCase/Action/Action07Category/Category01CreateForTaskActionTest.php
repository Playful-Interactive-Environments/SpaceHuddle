<?php

namespace App\Test\TestCase\Action\Action07Category;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\ParticipantTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Category\CategoryCreateForTaskAction
 */
class Category01CreateForTaskActionTest extends TestCase
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
        $this->taskId = $this->getFirstCategorisationTaskId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCategoryForTask(): void
    {
        $tableRowCount = $this->getTableRowCount("idea");
        $request = $this->createJsonRequest(
            "POST",
            "/task/$this->taskId/category/",
            [
                "keywords" => "php unit test category"
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "idea");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCategoryForTaskValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/task/$this->taskId/category/",
            [
                "description" => "php unit test category"
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
                            "field" => "keywords"
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
