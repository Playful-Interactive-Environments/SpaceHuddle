<?php

namespace App\Handler;

use App\Factory\LoggerFactory;
use App\Responder\Responder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Setup for default error handling
 */
trait HandlerSetupTrait
{
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
}
