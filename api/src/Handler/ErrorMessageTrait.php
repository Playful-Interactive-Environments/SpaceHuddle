<?php

namespace App\Handler;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

trait ErrorMessageTrait
{
    private LoggerInterface $errorLogger;

    /**
     * ErrorMessageTrait constructor.
     * @param LoggerFactory $loggerFactory The response factory
     */
    public function initErrorLog(LoggerFactory $loggerFactory)
    {
        $this->errorLogger = $loggerFactory
            ->addFileHandler("userError.log")
            ->createLogger();
    }

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
        $reasonPhrase = $this->responseFactory->createResponse()
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withStatus($statusCode)->getReasonPhrase();
        $errorMessage = sprintf("%s %s", $statusCode, $reasonPhrase);

        if ($displayErrorDetails === true) {
            $errorMessage = sprintf(
                "%s - Error details: %s",
                $errorMessage,
                $this->getExceptionText($exception)
            );
        }
        $debugOutput = [
            "status" => $statusCode,
            "error" => $errorMessage
        ];
        $this->errorLogger && $this->errorLogger->info(json_encode($debugOutput));

        return $errorMessage;
    }

    /**
     * Get the first error file outside of composer files.
     *
     * @param Throwable $exception The error
     * @return array<string, mixed> First error file outside of composer files.
     */
    private function getFirstNotComposerFile(Throwable $exception): array
    {
        $trace_list = $exception->getTrace();
        $traceIndex = 0;
        foreach ($trace_list as $trace) {
            if (!str_contains($trace["file"], "vendor")) {
                $file = $trace["file"];
                $line = $trace["line"];
                return [
                    "file" => $file,
                    "line" => $line,
                    "traceIndex" => $traceIndex
                ];
            }
            $traceIndex++;
        }
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
        $fileInfo = $this->getFirstNotComposerFile($exception);
        $file = $fileInfo["file"];# $exception->getFile();
        $line = $fileInfo["line"];# $exception->getLine();
        $traceIndex = $fileInfo["traceIndex"];# 0;
        $message = $exception->getMessage();
        $trace = $exception->getTraceAsString();
        $error = sprintf("[%s] %s in trace index %s - %s on line %s.", $code, $message, $traceIndex, $file, $line);
        $error .= sprintf("\nBacktrace:\n%s", $trace);

        if ($maxLength > 0) {
            $error = substr($error, 0, $maxLength);
        }

        return $error;
    }

    /**
     * Log error
     * @param ServerRequestInterface $request The request
     * @param Throwable $exception The exception
     * @param bool $logErrors Log errors
     * @return void
     */
    protected function logError(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $logErrors
    ): void {
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
    }

    /**
     * Default implementation for handle the exception
     *
     * @param ServerRequestInterface $request The request
     * @param Throwable $exception The exception
     * @param bool $logErrors Log errors
     * @param int $statusCode Error status code
     * @param string $errorMessage Error message
     *
     * @return ResponseInterface The response
     */
    public function handleException(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $logErrors,
        int $statusCode,
        string $errorMessage
    ): ResponseInterface {
        // Log error
        $this->logError($request, $exception, $logErrors);

        // Render response
        $response = $this->responseFactory->createResponse()
            ->withHeader('Access-Control-Allow-Origin', '*');
        $response = $this->responder->withJson($response, [
            "error" => [
                "message" => $errorMessage,
            ],
        ]);

        return $response->withStatus($statusCode);
    }
}
