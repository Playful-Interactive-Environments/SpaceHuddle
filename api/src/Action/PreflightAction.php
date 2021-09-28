<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Preflight action for CORS.
 *
 * @OA\Response(
 *   response="CORS",
 *   description="Default CORS response.",
 *   @OA\Header(
 *     header="Access-Control-Allow-Origin",
 *     @OA\Schema( type="string" )
 *   ),
 *   @OA\Header(
 *     header="Access-Control-Allow-Methods",
 *     @OA\Schema( type="string" )
 *   ),
 *   @OA\Header(
 *     header="Access-Control-Allow-Headers",
 *     @OA\Schema( type="string" )
 *   )
 * )
 *
 * @OA\Options(
 *   path="/participant/connect/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 * )
 * @OA\Options(
 *   path="/session/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 * )
 * @OA\Options(
 *   path="/sessions/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 * )
 * @OA\Options(
 *   path="/sessions/{id}/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 * )
 * @OA\Options(
 *   path="/user/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 *   )
 * )
 * @OA\Options(
 *   path="/user/login/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 * )
 * @OA\Options(
 *   path="/user/register/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
 *   )
 * )
 * @OA\Options(
 *   path="/module/",
 *   summary="CORS Preflight check",
 *   tags={"CORS"},
 *   @OA\Response(response="200", ref="#/components/responses/CORS")
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
