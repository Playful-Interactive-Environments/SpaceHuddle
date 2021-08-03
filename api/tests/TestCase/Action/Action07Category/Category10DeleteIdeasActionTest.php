<?php

namespace App\Test\TestCase\Action\Action07Category;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Category\CategoryIdeaDeleteAction
 */
class Category10DeleteIdeasActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $categoryId;
    protected ?string $ideaId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->categoryId = $this->getFirstCategoryId();
        $this->ideaId = $this->getFirstIdeaId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDeleteIdeasFromCategory(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/category/$this->categoryId/ideas",
            [$this->ideaId]
        );
        $request = $this->withJwtAuth($request);
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
    public function testDeleteIdeasFromCategoryInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/category/$this->categoryId/ideas",
            [$this->ideaId]
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
                            "message" => "NotValid: Not all ideas are linked to the category.",
                            "field" => "ideas"
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
