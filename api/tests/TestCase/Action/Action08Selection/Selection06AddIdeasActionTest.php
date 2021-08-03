<?php

namespace App\Test\TestCase\Action\Action08Selection;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Selection\SelectionIdeaAddAction
 */
class Selection06AddIdeasActionTest extends TestCase
{
    use AppTestTrait {
        AppTestTrait::setUp as private setUpAppTrait;
    }
    use UserTestTrait;

    protected ?string $selectionId;
    protected ?string $ideaId;

    /**
     * Before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpAppTrait();
        $this->selectionId = $this->getFirstSelectionId();
        $this->ideaId = $this->getFirstIdeaId();
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAddIdeaToSelection(): void
    {
        $tableRowCount = $this->getTableRowCount("selection_idea");
        $request = $this->createJsonRequest(
            "POST",
            "/selection/$this->selectionId/ideas/",
            [
                $this->ideaId
            ]
        );
        $request = $this->withJwtAuth($request);
        $response = $this->app->handle($request);

        // Check response
        $this->assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertJsonContentType($response);

        // Check database
        $this->assertTableRowCount($tableRowCount+1, "selection_idea");
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAddIdeaToSelectionValidation(): void
    {
        $request = $this->createJsonRequest(
            "POST",
            "/selection/$this->selectionId/ideas/", []
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
                            "message" => "Empty: This field cannot be left empty",
                            "field" => "ideas"
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
