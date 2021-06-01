<?php

namespace App\Action\OpenApi;

use App\Responder\Responder;
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GAB API Documentation.
 *
 * @OA\Info(title="GAB API", version="0.1")
 * @OA\Schemes(format="https")
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   in="header",
 *   name="Authorization",
 *   type="http",
 *   scheme="Bearer",
 *   bearerFormat="JWT"
 * )
 * @OA\Server(url="../../")
 * @OA\Response(
 *   response="200",
 *   description="OK."
 * )
 * @OA\Response(
 *   response="201",
 *   description="Created."
 * )
 * @OA\Response(
 *   response="204",
 *   description="Successfully processed."
 * )
 * @OA\Response(
 *   response="400",
 *   description="Bad request.",
 *   @OA\JsonContent(
 *     ref="#/components/schemas/Error",
 *     example={
 *       "error": {
 *         "message": "Your request does not seem to be valid.",
 *         "details": {
 *           { "message": "A more detailed report, if available." }
 *         }
 *       }
 *     }
 *   )
 * )
 * @OA\Response(
 *   response="401",
 *   description="Authorization information is missing or invalid.",
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * @OA\Response(
 *   response="403",
 *   description="Forbidden.",
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * @OA\Response(
 *   response="404",
 *   description="Not Found.",
 *   @OA\JsonContent(ref="#/components/schemas/Error", example={"error": {"message": "Not found"}})
 * )
 * @OA\Response(
 *   response="405",
 *   description="Method not allowed.",
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * @OA\Response(
 *   response="422",
 *   description="Unprocessable Entity.",
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * @OA\Response(
 *   response="500",
 *   description="Unexpected error.",
 *   @OA\JsonContent(ref="#/components/schemas/Error")
 * )
 * @OA\Schema(
 *   schema="Error",
 *   required={"error"},
 *   @OA\Property(
 *     property="error",
 *     type="object",
 *     required={"message"},
 *     @OA\Property(
 *       property="message",
 *       type="string",
 *       description="The error message"
 *     ),
 *     @OA\Property(
 *       property="details",
 *       type="array",
 *       description="The error details (e.g. validation errors)",
 *       @OA\Items(
 *         ref="#/components/schemas/ErrorDetails"
 *       )
 *     )
 *   )
 * )
 * @OA\Schema(
 *   schema="ErrorDetails",
 *   @OA\Property(
 *     property="field",
 *     type="string",
 *     description="The name of the invalid field"
 *   ),
 *   @OA\Property(
 *     property="message",
 *     type="string",
 *     description="The error message"
 *   )
 * )
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
