<?php

namespace App\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Class AuthorisationErrorHandling
 * @package App\Domain\Base\Data
 */
class AuthorisationErrorHandling implements ErrorHandlerInterface
{
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
        http_response_code(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
        $errorMessage = $exception->getMessage();
        $error = json_encode(
            [
                "state" => "Failed",
                "message" => "Authorisation error occurred: " . $errorMessage
            ]
        );
        die($error);
    }
}
