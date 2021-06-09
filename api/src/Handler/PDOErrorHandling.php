<?php

namespace App\Handler;

use App\Database\TransactionInterface;
use Fig\Http\Message\StatusCodeInterface;
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
    protected TransactionInterface $transaction;

    /**
     * The constructor.
     * @param TransactionInterface $transaction The transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
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
        http_response_code(StatusCodeInterface::STATUS_FAILED_DEPENDENCY);
        $errorMessage = $exception->getMessage();
        $this->transaction->rollback();
        $error = json_encode(
            [
                "state" => "Failed",
                "message" => "Error occurred: " . $errorMessage
            ]
        );
        die($error);
    }
}
