<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Preflight action for CORS.
 *
 * @OA\Options(
 *   path="/api/*",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(
 *     response="200",
 *     description="Default CORS response.",
 *     @OA\Header(
 *       header="Access-Control-Allow-Origin",
 *       @OA\Schema(type="string")
 *     ),
 *     @OA\Header(
 *       header="Access-Control-Allow-Methods",
 *       @OA\Schema(type="string")
 *     ),
 *     @OA\Header(
 *       header="Access-Control-Allow-Headers",
 *       @OA\Schema(type="string")
 *     )
 *   )
 * )
 */
final class PreflightAction
{
    /**
     * Invoke the action.
     * @param ServerRequestInterface $request The request.
     * @param ResponseInterface $response The response.
     * @return ResponseInterface The updated response.
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Do nothing here. Just return the response.
        return $response;
    }
}
