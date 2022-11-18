<?php

namespace App\Handler;

use App\Factory\LoggerFactory;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Class GenericErrorHandling
 * @package App\Domain\Base\Data
 */
class GenericErrorHandling implements ErrorHandlerInterface
{
    use HandlerSetupTrait {
        HandlerSetupTrait::__construct as private setUp;
    }
    use ErrorMessageTrait;

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
        $this->setUp($responder, $responseFactory, $loggerFactory);
        $this->initErrorLog($loggerFactory);
    }

    /**
     * Invoke.
     *
     * @param ServerRequestInterface $request The request
     * @param Throwable $exception The exception
     * @param bool $displayErrorDetails Show error details
     * @param bool $logErrors Log errors
     * @param bool $logErrorDetails Log error details
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $statusCode = StatusCodeInterface::STATUS_PRECONDITION_REQUIRED;
        $errorMessage = "Generic Error occurred: ".$this->
            getErrorMessage($exception, $statusCode, $displayErrorDetails);

        return $this->handleException(
            $request,
            $exception,
            $logErrors,
            $statusCode,
            $errorMessage
        );
    }
}
