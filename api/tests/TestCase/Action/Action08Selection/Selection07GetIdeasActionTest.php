<?php

namespace App\Test\TestCase\Action\Action08Selection;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Selection\SelectionIdeaReadAction
 */
class Selection07GetIdeasActionTest extends TestCase
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
    public function testGetAllIdeasFromSelection(): void
    {
        $request = $this->createJsonRequest(
            "GET",
            "/selection/$this->selectionId/ideas/"
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
    public function testGetAllIdeasFromSelectionWithoutLogin(): void
    {
        $request = $this->createRequest("GET", "/selection/$this->selectionId/ideas/");
        $request = $this->withJwtAuth($request)->withoutHeader("Authorization");
        $response = $this->app->handle($request);

        $this->assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }
}
