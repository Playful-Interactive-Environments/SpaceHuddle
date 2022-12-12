<?php

namespace App\Middleware;

use App\Data\AuthorisationData;
use App\Routing\JwtAuth;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * JWT Claim middleware.
 */
final class JwtClaimMiddleware implements MiddlewareInterface
{
    /**
     * @var JwtAuth
     */
    private JwtAuth $jwtAuth;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;
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
        $this->logger = $loggerFactory
            ->addFileHandler("jwtClaimDebug.log")
            ->createLogger();
        $this->errorLogger = $loggerFactory
            ->addFileHandler("userError.log")
            ->createLogger();
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
        $authorization = explode(" ", (string)$request->getHeaderLine("Authorization"));
        $type = $authorization[0] ?? "";
        $credentials = $authorization[1] ?? "";
        $userAgent = explode(" ", (string)$request->getHeaderLine("User-Agent"));
        $debugOutput = [
            "uri" => json_encode($request->getUri()->getPath()),
            "browser" => $userAgent[count($userAgent) - 1],
            "type" => $request->getMethod(),
            "header" => json_encode($request->getHeaders())
        ];
        $this->logger && $this->logger->info(json_encode($debugOutput));
        if ($type !== "Bearer") {
            // Append the authorisation as request attribute
            $request = $request->withAttribute("authorisation", new AuthorisationData());
            return $handler->handle($request);
        }
        $token = $this->jwtAuth->validateToken($credentials);

        if ($token) {
            // Append valid token
            $request = $request->withAttribute("token", $token);
            // Append the user id as request attribute
            $request = $request->withAttribute("action", $token->claims()->get("action"));
            // Append the user id as request attribute
            $request = $request->withAttribute("userId", $token->claims()->get("userId"));
            // Append the username as request attribute
            $request = $request->withAttribute("username", $token->claims()->get("username"));
            // Append the user id as request attribute
            $request = $request->withAttribute("participantId", $token->claims()->get("participantId"));
            // Append the username as request attribute
            $request = $request->withAttribute("browserKey", $token->claims()->get("browserKey"));
            // Append the authorisation as request attribute
            $request = $request->withAttribute("authorisation", new AuthorisationData($token->claims()));
        } else {
            $debugOutput = [
                "status" => "JwtClaim Unauthorized",
                "uri" => json_encode($request->getUri()->getPath()),
                "type" => $request->getMethod(),
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
