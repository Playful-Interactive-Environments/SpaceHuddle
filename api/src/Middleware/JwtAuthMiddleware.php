<?php

namespace App\Middleware;

use App\Routing\JwtAuth;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * JWT Auth middleware.
 */
final class JwtAuthMiddleware implements MiddlewareInterface
{
    /**
     * @var JwtAuth
     */
    private JwtAuth $jwtAuth;
    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $errorLogger;

    /**
     * The constructor.
     *
     * @param JwtAuth $jwtAuth The JWT auth
     * @param ResponseFactoryInterface $responseFactory The response factory
     * @param LoggerFactory $loggerFactory The response factory
     */
    public function __construct(
        JwtAuth $jwtAuth,
        ResponseFactoryInterface $responseFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->jwtAuth = $jwtAuth;
        $this->responseFactory = $responseFactory;
        /*$this->errorLogger = $loggerFactory
            ->addFileHandler("userError.log")
            ->createLogger();*/
    }

    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $action = $request->getMethod();
        if ($action == "OPTIONS") {
            return $handler->handle($request);
        }

        $token = explode(" ", (string)$request->getHeaderLine("Authorization"))[1] ?? "";
        if (!$token || !$this->jwtAuth->validateToken($token)) {
            $debugOutput = [
                "status" => "JwtAut Unauthorized",
                "uri" => json_encode($request->getUri()->getPath()),
                "header" => json_encode($request->getHeaders())
            ];
            $this->errorLogger && $this->errorLogger->info(json_encode($debugOutput));
            return $this->responseFactory->createResponse()
                ->withHeader("Content-Type", "application/json")
                //->withHeader('Access-Control-Allow-Origin', '*')
                ->withStatus(401, "Unauthorized");
        }
        return $handler->handle($request);
    }
}
