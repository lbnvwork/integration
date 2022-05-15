<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;
use Plus\Proxy;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractProcessor
{
    /** @var AbstractSettings */
    protected $settings;

    /** @var AbstractFlow */
    protected $flow;

    /**
     * @param AbstractSettings  $settings
     * @param                   $flow
     */
    public function __construct(AbstractSettings $settings, $flow)
    {
        $this->settings = $settings;
        $this->flow = $flow;
        $this->createCoreOutputHandler();
    }

    /**
     * @return void
     */
    abstract public function process(): void;


    /**
     * @return void
     */
    abstract protected function createCoreOutputHandler(): void;

    /**
     * @param string $url
     * @param array  $headers
     * @param array  $body
     * @param array  $maskedHeaders
     * @param string $httpMethod
     *
     * @return ResponseInterface
     * @throws RequestException
     */
    protected function sendRequest(
        string $url,
        array $headers,
        array $body = [],
        array $maskedHeaders = [],
        string $httpMethod = HttpRequest::METHOD_POST
    ): ResponseInterface {
        $options = [
            RequestOptions::HEADERS => $headers,
        ];
        if (!empty($body)) {
            $options[RequestOptions::JSON] = $body;
        }

        Proxy::init()->getErrorHandler()->setFinancialRiskState($this->settings->isFinRisk());

        return
            Proxy::init()
                ->getHttpClient()
                ->withHeadersFormatter($maskedHeaders)
                ->request($httpMethod, $url, $options);
    }

    /**
     * @param string $jsonString
     *
     * @return array
     *
     * @throws MalformedResponseException
     */
    protected function parseJson(string $jsonString): array
    {
        try {
            $data = \GuzzleHttp\json_decode($jsonString, true);
        } catch (InvalidArgumentException $ex) {
            throw new MalformedResponseException(
                'Invalid PS response body: [JSON_DECODE:' . $ex->getMessage() . ']'
            );
        }

        Proxy::init()->getValidator()->validate(
            $data,
            [
                new Assert\NotBlank(),
                new Assert\Type('array'),
            ],
            'Invalid PS response body: unexpected response body',
            MalformedResponseException::class
        );

        return is_array($data) ? $data : [];
    }

    /**
     * @return array
     */
    protected function getDefaultCheckStatusData(): array
    {
        $request = $this->settings->getCoreRequest();

        return [
            AbstractPayoutCoreHandler::CHECK_STATUS_SERVICE_NAME   => $request->getServiceName(),
            AbstractPayoutCoreHandler::CHECK_STATUS_OPERATION_TYPE => $request->getType(),
        ];
    }

    /**
     * @param string $url
     * @param array  $body
     * @param string $httpMethod
     *
     * @return ResponseInterface
     */
    protected function getHttpResponse(
        string $url,
        array $body = [],
        string $httpMethod = HttpRequest::METHOD_POST
    ): ResponseInterface {
        try {
            $headers = $this->settings->getHeaders();
            $httpResponse = $this->sendRequest(
                $url,
                $headers->getAll(),
                $body,
                $headers->getMaskedFields(),
                $httpMethod
            );
        } catch (ClientException  $e) {
            $httpResponse = $e->getResponse();
        }

        return $httpResponse;
    }

    /**
     * @param RequestException $exception
     *
     * @return void
     */
    protected function getExceptionMessage(RequestException $exception): string
    {
        $httpResponse = $exception->getResponse();

        return (string)!is_null($httpResponse) ? $httpResponse->getStatusCode() : $exception->getCode();
    }
}
