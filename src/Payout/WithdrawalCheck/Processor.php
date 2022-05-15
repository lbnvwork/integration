<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\InvalidAmountOrCurrencyException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings as PayoutSettings;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\CoreHandler as CoreOutputHandler;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Processor extends AbstractProcessor
{
    /** @var PayoutSettings */
    protected $settings;

    /** @var CoreHandler */
    protected $coreOutputHandler;

    /**
     * @return void
     * @throws Exception
     */
    public function process(): void
    {
        $withdrawalId = $this->settings->getWithdrawalId();
        $coreRequest = $this->settings->getCoreRequest();

        try {
            $httpResponse = $this->getHttpResponse(
                $this->settings->getHeaders()->getUrlByTemplate(
                    $this->settings->getMerchantParams()->getWithdrawalCheckUrlTemplate(),
                    $withdrawalId
                ),
                [],
                HttpRequest::METHOD_GET
            );

            $responseBody = $this->parseJson($httpResponse->getBody());

            $psResponseHandler = new PsResponseHandler(
                $responseBody,
                $httpResponse->getStatusCode(),
                $coreRequest->getOperationId(),
                $coreRequest->getAmount(),
                $coreRequest->getCurrency(),
                $withdrawalId
            );

            $psParams = new PsParams($responseBody);
            $psResponseHandler->setParameters($psParams);

            $this->coreOutputHandler
                ->setPsParams($psParams)
                ->setResponseStatusCode($httpResponse->getStatusCode());

            $psResponseHandler->validate();
        } catch (RequestException $e) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::NETWORK_ERROR);
        } catch (MalformedResponseException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::MALFORMED);
        } catch (InvalidAmountOrCurrencyException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::INVALID_AMOUNT_OR_CURRENCY);
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
