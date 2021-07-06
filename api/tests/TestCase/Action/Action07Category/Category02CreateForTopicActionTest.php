<?php

namespace App\Test\TestCase\Action\Action07Category;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Category\CategoryCreateForTopicAction
 */
class Category02CreateForTopicActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $topicId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->topicId = $this->getFirstTopicId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCategoryForTopic(): void
    {
        $tableRowCount = $this->getTableRowCount("idea");
        $request = $this->createJsonRequest(
            "POST",
            "/topic/$this->topicId/category/",
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
    public function testCreateCategoryForTopicValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/topic/$this->topicId/category/",
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
