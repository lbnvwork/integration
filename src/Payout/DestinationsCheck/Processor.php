<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck\CoreHandler as CoreOutputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Processor extends AbstractProcessor
{
    /** @var Settings */
    protected $settings;

    /** @var CoreHandler */
    protected $coreOutputHandler;

    /**
     * @return void
     * @throws Exception
     */
    public function process(): void
    {
        $merchantParams = $this->settings->getMerchantParams();
        $destinationsId = $this->settings->getDestinationsId();
        $coreRequest = $this->settings->getCoreRequest();

        try {
            $httpResponse = $this->getHttpResponse(
                $this->settings->getHeaders()->getUrlByTemplate(
                    $merchantParams->getDestinationsCheckUrlTemplate(),
                    $destinationsId
                ),
                [],
                HttpRequest::METHOD_GET
            );

            $psResponseHandler = new PsResponseHandler(
                $httpResponse->getStatusCode(),
                $this->parseJson($httpResponse->getBody()),
                $coreRequest->getOperationId(),
                $coreRequest->getCurrency(),
                $destinationsId
            );
            $psResponseHandler->handle();

            /** @var PsParams $psParams */
            $psParams = $psResponseHandler->getParameters();
            $responseStatusCode = $httpResponse->getStatusCode();

            $this->coreOutputHandler
                ->setPsParams($psParams)
                ->setResponseStatusCode($responseStatusCode);
        } catch (RequestException $e) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::NETWORK_ERROR);
            $this->coreOutputHandler->setResponseExceptionMessage($this->getExceptionMessage($e));
        } catch (MalformedResponseException $e) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::MALFORMED);
            $this->coreOutputHandler->setResponseExceptionMessage($e->getMessage());
        }
        $this->coreOutputHandler->handle();
    }

    /**
     * @return void
     */
    protected function createCoreOutputHandler(): void
    {
        $this->coreOutputHandler = (new CoreOutputHandler(
            $this->settings->getResponseToCore(),
            $this->flow,
            $this->getDefaultCheckStatusData()
        ));
    }
}
