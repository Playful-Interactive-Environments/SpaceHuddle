<?php

namespace App\Handler;

use App\Database\TransactionInterface;
use App\Factory\LoggerFactory;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Implement PDOException error handling for middleware
 * @package App\Domain\Base
 */
class PDOErrorHandling implements ErrorHandlerInterface
{
    use HandlerSetupTrait {
        HandlerSetupTrait::__construct as private setUp;
    }
    use ErrorMessageTrait;

    protected TransactionInterface $transaction;
    private ResponseFactoryInterface $responseFactory;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResponseFactoryInterface $responseFactory The response factory
     * @param LoggerFactory $loggerFactory The logger factory
     * @param TransactionInterface $transaction The transaction
     */
    public function __construct(
        Responder $responder,
        ResponseFactoryInterface $responseFactory,
        LoggerFactory $loggerFactory,
        TransactionInterface $transaction
    ) {
        $this->setUp($responder, $responseFactory, $loggerFactory);
        $this->transaction = $transaction;
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
        $this->transaction->rollback();
        $statusCode = StatusCodeInterface::STATUS_FAILED_DEPENDENCY;

        // Error message
        $errorMessage = "Database exception: ".$exception->getMessage();

        if ($displayErrorDetails) {
            $trace_list = $exception->getTrace();
            foreach ($trace_list as $trace) {
                if (!str_contains($trace["file"], "vendor")) {
                    $file = $trace["file"];
                    $line = $trace["line"];
                    $errorMessage = "$errorMessage ($file: $line)";
                    break;
                }
            }

            #$errorMessage = $this->getErrorMessage($exception, $statusCode, $displayErrorDetails);
        }

        return $this->handleException(
            $request,
            $exception,
            $logErrors,
            $statusCode,
            $errorMessage
        );
    }
}
