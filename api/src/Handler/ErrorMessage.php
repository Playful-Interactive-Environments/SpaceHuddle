<?php

namespace App\Handler;
use Throwable;

trait ErrorMessage
{
    /**
     * Get error message.
     *
     * @param Throwable $exception The error
     * @param int $statusCode The http status code
     * @param bool $displayErrorDetails Display details
     *
     * @return string The message
     */
    private function getErrorMessage(Throwable $exception, int $statusCode, bool $displayErrorDetails): string
    {
        $reasonPhrase = $this->responseFactory->createResponse()->withStatus($statusCode)->getReasonPhrase();
        $errorMessage = sprintf("%s %s", $statusCode, $reasonPhrase);

        if ($displayErrorDetails === true) {
            $errorMessage = sprintf(
                "%s - Error details: %s",
                $errorMessage,
                $this->getExceptionText($exception)
            );
        }

        return $errorMessage;
    }

    /**
     * Get exception text.
     *
     * @param Throwable $exception Error
     * @param int $maxLength The max length of the error message
     *
     * @return string The full error message
     */
    private function getExceptionText(Throwable $exception, int $maxLength = 0): string
    {
        $code = $exception->getCode();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $message = $exception->getMessage();
        $trace = $exception->getTraceAsString();
        $error = sprintf("[%s] %s in %s on line %s.", $code, $message, $file, $line);
        $error .= sprintf("\nBacktrace:\n%s", $trace);

        if ($maxLength > 0) {
            $error = substr($error, 0, $maxLength);
        }

        return $error;
    }
}
