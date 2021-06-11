<?php

namespace App\Handler;

use App\Database\TransactionInterface;
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
    use ErrorMessage;
    protected TransactionInterface $transaction;
    private ResponseFactoryInterface $responseFactory;

    /**
     * The constructor.
     * @param TransactionInterface $transaction The transaction
     * @param ResponseFactoryInterface $responseFactory The response factory
     */
    public function __construct(
        TransactionInterface $transaction,
        ResponseFactoryInterface $responseFactory
    )
    {
        $this->transaction = $transaction;
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
        $this->transaction->rollback();
        $statusCode = StatusCodeInterface::STATUS_FAILED_DEPENDENCY;
        http_response_code($statusCode);

        // Error message
        $errorMessage = $exception->getMessage();

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

        $error = json_encode(
            [
                "state" => "Failed",
                "message" => "Database exception: $errorMessage"
            ]
        );
        die($error);
    }
}
