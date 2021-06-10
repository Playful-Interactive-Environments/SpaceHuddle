<?php


namespace App\Action\Base;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Domain\Base\Service\AbstractService;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Description of the common action functionality.
 * @package App\Action\Base
 */
abstract class AbstractAction
{
    protected Responder $responder;
    protected AbstractService $service;
    protected int $successStatusCode;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param AbstractService $service The service
     */
    public function __construct(Responder $responder, AbstractService $service)
    {
        $this->responder = $responder;
        $this->service = $service;
        $this->successStatusCode = StatusCodeInterface::STATUS_OK;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     * @param array $args Url parameter
     *
     * @return ResponseInterface The response
     * @throws AuthorisationException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Extract the form data from the request body
        $bodyData = (array)$request->getParsedBody();
        $authorisation = $request->getAttribute("authorisation");

        $result = $this->executeService($authorisation, $bodyData, $args);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result)
            ->withStatus($this->successStatusCode);
    }

    /**
     * Execute specific service functionality
     * @param AuthorisationData $authorisation Authorisation token data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     * @return mixed service result
     * @throws AuthorisationException
     */
    protected function executeService(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ) : mixed {
        return $this->service->service($authorisation, $bodyData, $urlData);
    }
}
