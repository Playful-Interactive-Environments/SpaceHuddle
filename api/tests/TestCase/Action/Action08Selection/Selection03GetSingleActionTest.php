<?php

namespace App\Test\TestCase\Action\Action08Selection;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Selection\SelectionReadSingleAction
 */
class Selection03GetSingleActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $selectionId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->selectionId = $this->getFirstSelectionId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGetSingleSelection(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/selection/$this->selectionId/"
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertJsonContentType($response);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGetSingleSelectionInvalidId(): void
    {
        $request = $this->createRequest("GET", "/selection/xxx/");
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
