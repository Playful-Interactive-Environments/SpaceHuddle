<?php

namespace App\Domain\Base;

use App\Factory\QueryFactory;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Implement PDOException error handling for middleware
 * @package App\Domain\Base
 */
class RepositoryErrorHandling implements ErrorHandlerInterface
{
    protected QueryFactory $queryFactory;

    /**
     * The constructor.
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
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
        $this->queryFactory->rollbackTransaction();
        $error = json_encode(
            [
                "state" => "Failed",
                "message" => "Error occurred: " . $errorMessage
            ]
        );
        die($error);
    }
}
