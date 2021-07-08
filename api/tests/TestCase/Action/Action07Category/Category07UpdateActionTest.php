<?php

namespace App\Test\TestCase\Action\Action07Category;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Category\CategoryUpdateAction
 */
class Category07UpdateActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $categoryId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->categoryId = $this->getFirstCategoryId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testUpdateCategory(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/category/",
            [
                "id" => $this->categoryId,
                "description" => "test"
            ]
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
    public function testUpdateCategoryInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/category/",
            [
                "id" => "xxx",
                "description" => null
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
