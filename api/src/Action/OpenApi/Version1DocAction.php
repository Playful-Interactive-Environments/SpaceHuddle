<?php

namespace App\Action\OpenApi;

use App\Responder\Responder;
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class Version1DocAction
{
    private Responder $responder;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     */
    public function __construct(Responder $responder)
    {
        $this->responder = $responder;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewData = [
            "spec" => Generator::scan(
                [
                    __DIR__ . "/../../../src/",
                ]
            )->toJson()
        ];

        return $this->responder->withTemplate($response, "doc/swagger.php", $viewData);
    }
}
