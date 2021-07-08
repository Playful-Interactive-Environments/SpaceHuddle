<?php

namespace App\Test\TestCase\Action\Action08Selection;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Selection\SelectionUpdateAction
 */
class Selection05UpdateActionTest extends TestCase
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
    public function testUpdateSelection(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/selection/",
            [
                "id" => $this->selectionId,
                "name" => "php unit test selection update"
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
    public function testUpdateSelectionInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "PUT",
            "/selection/",
            [
                "id" => "xxx",
                "name" => null
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());
    }
}
