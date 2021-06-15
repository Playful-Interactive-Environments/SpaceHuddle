<?php

namespace App\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Class AuthorisationErrorHandling
 * @package App\Domain\Base\Data
 */
class GenericErrorHandling implements ErrorHandlerInterface
{
    use ErrorMessage;
    private ResponseFactoryInterface $responseFactory;

    /**
     * The constructor.
     * @param ResponseFactoryInterface $responseFactory The response factory
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory
    )
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Throwable              $exception
     * @param bool                   $displayErrorDetails
     * @param bool                   $logErrors
     * @param bool                   $logErrorDetails
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $statusCode = StatusCodeInterface::STATUS_PRECONDITION_REQUIRED;
        http_response_code($statusCode);
        $errorMessage = $this->getErrorMessage($exception, $statusCode, $displayErrorDetails);
        $error = json_encode(
            [
                "state" => "Failed",
                "message" => "Generic Error occurred: " . $errorMessage
            ]
        );
        die($error);
    }
}
