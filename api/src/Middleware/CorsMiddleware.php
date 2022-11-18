<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Slim\Routing\RouteContext;

/**
 * CORS middleware.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory The response factory
     */
    public function __construct(
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory
            ->addFileHandler("coreDebug.log")
            ->createLogger();
    }

    /**
     * Invoke middleware.
     **
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     **
     * @return ResponseInterface The response
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $response = $handler->handle($request);
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $methods))
            ->withHeader('Access-Control-Allow-Headers', $requestHeaders ?: '*')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withAddedHeader('Cache-Control', 'post-check=0, pre-check=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Access-Control-Allow-Credentials', 'true');

        $userAgent = explode(" ", (string)$request->getHeaderLine("User-Agent"));
        $debugOutput = [
            "uri" => json_encode($request->getUri()->getPath()),
            "browser" => $userAgent[count($userAgent) - 1],
            "response" => json_encode($response->getHeaders()),
            "header" => json_encode($request->getHeaders())
        ];
        $this->logger && $this->logger->info(json_encode($debugOutput));
        // Optional: Allow Ajax CORS requests with Authorization header
        return $response;
    }
}
