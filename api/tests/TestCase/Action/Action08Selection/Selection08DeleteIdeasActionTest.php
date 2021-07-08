<?php

namespace App\Test\TestCase\Action\Action08Selection;

use App\Test\Traits\AppTestTrait;
use App\Test\Traits\UserTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Monolog\Test\TestCase;

/**
 * Test.
 *
 * @coversDefaultClass \App\Action\Selection\SelectionIdeaDeleteAction
 */
class Selection08DeleteIdeasActionTest extends TestCase
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
    public function testDeleteIdeasFromSelection(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/selection/$this->selectionId/ideas",
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
    public function testDeleteIdeasFromSelectionInvalidId(): void
    {
        $request = $this->createJsonRequest(
            "DELETE",
            "/selection/$this->selectionId/ideas",
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
                            "message" => "Not all ideas are linked to the selection.",
                            "field" => "ideas"
                        ]
                    ],
                ],
            ],
            $response
        );
    }
}
