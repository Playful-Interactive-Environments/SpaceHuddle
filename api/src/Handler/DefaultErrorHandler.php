<?php

namespace App\Handler;

use App\Factory\LoggerFactory;
use App\Responder\Responder;
use DomainException;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Throwable;

/**
 * Default Error Renderer.
 */
final class DefaultErrorHandler
{
    use ErrorMessage;

    private Responder $responder;

    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResponseFactoryInterface $responseFactory The response factory
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        Responder $responder,
        ResponseFactoryInterface $responseFactory,
        LoggerFactory $loggerFactory
    ) {
        $this->responder = $responder;
        $this->responseFactory = $responseFactory;
        $this->logger = $loggerFactory
            ->addFileHandler("error.log")
            ->createLogger();
    }

    /**
     * Invoke.
     *
     * @param ServerRequestInterface $request The request
     * @param Throwable $exception The exception
     * @param bool $displayErrorDetails Show error details
     * @param bool $logErrors Log errors
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors
    ): ResponseInterface {
        // Log error
        if ($logErrors) {
            $this->logger->error(
                sprintf(
                    "Error: [%s] %s, Method: %s, Path: %s",
                    $exception->getCode(),
                    $this->getExceptionText($exception),
                    $request->getMethod(),
                    $request->getUri()->getPath()
                )
            );
        }

        // Detect status code
        $statusCode = $this->getHttpStatusCode($exception);

        // Error message
        $errorMessage = $this->getErrorMessage($exception, $statusCode, $displayErrorDetails);

        // Render response
        $response = $this->responseFactory->createResponse();
        $response = $this->responder->withJson($response, [
            "error" => [
                "message" => $errorMessage,
            ],
        ]);

        return $response->withStatus($statusCode);
    }

    /**
     * Get http status code.
     *
     * @param Throwable $exception The exception
     *
     * @return int The http code
     */
    private function getHttpStatusCode(Throwable $exception): int
    {
        // Detect status code
        $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpException) {
            $statusCode = (int)$exception->getCode();
        }

        if ($exception instanceof DomainException || $exception instanceof InvalidArgumentException) {
            // Bad request
            $statusCode = StatusCodeInterface::STATUS_BAD_REQUEST;
        }

        $file = basename($exception->getFile());
        if ($file === "CallableResolver.php") {
            $statusCode = StatusCodeInterface::STATUS_NOT_FOUND;
        }

        return $statusCode;
    }
}
