<?php


namespace App\Action\Base;

use App\Data\AuthorisationData;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Description of the common action functionality.
 */
trait ActionTrait
{
    protected Responder $responder;
    protected int $successStatusCode;

    /**
     * Basic setup for constructor.
     *
     * @param Responder $responder The responder
     * @return void
     */
    protected function setUp(Responder $responder): void
    {
        $this->responder = $responder;
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
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Extract the form data from the request body
        $bodyData = (array)$request->getParsedBody();
        $authorisation = $request->getAttribute("authorisation");

        foreach ($args as $key => $value) {
            if ($value === "{{$key}}") {
                $args[$key] = null;
            }
        }
        $urlParameter =  array_merge($args, $request->getQueryParams());

        $result = $this->executeService($authorisation, $bodyData, $urlParameter);

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
     */
    protected function executeService(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ) : mixed {
        return $this->service->service($authorisation, $bodyData, $urlData);
    }
}
