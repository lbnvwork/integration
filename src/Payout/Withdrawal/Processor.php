<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Withdrawal;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\InvalidAmountOrCurrencyException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedWithValidIdException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings as PayoutSettings;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Withdrawal\CoreHandler as CoreOutputHandler;

class Processor extends AbstractProcessor
{
    //output parameters
    private const
        WALLET = 'wallet',
        DESTINATION = 'destination',
        EXTERNAL_ID = 'externalID',
        AMOUNT = 'amount',
        CURRENCY = 'currency',
        BODY_GROUP = 'body';

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
        try {
            $httpResponse = $this->getHttpResponse(
                $this->settings->getMerchantParams()->getWithdrawalUrlTemplate(),
                $this->getRequestBody(),
            );

            $coreRequest = $this->settings->getCoreRequest();
            $responseBody = $this->parseJson($httpResponse->getBody());
            $responseStatusCode = $httpResponse->getStatusCode();

            $psResponseHandler = (new PsResponseHandler(
                $responseStatusCode,
                $responseBody,
                $coreRequest->getAmount(),
                $coreRequest->getCurrency(),
                $coreRequest->getOperationId()
            ));

            $psParams = new PsParams($responseBody);
            $psResponseHandler->setParameters($psParams);

            $this->coreOutputHandler
                ->setPsParams($psParams)
                ->setResponseStatusCode($responseStatusCode);

            $psResponseHandler->validate();
        } catch (RequestException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::NETWORK_ERROR);
            $this->coreOutputHandler->setResponseExceptionMessage($this->getExceptionMessage($exception));
        } catch (MalformedResponseException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::MALFORMED);
            $this->coreOutputHandler->setResponseExceptionMessage($exception->getMessage());
        } catch (InvalidAmountOrCurrencyException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::INVALID_AMOUNT_OR_CURRENCY);
            $this->coreOutputHandler->setResponseExceptionMessage($exception->getMessage());
        } catch (MalformedWithValidIdException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::PENDING);
            $this->coreOutputHandler->setResponseExceptionMessage($exception->getMessage());
        }

        $this->coreOutputHandler->handle();
    }

    /**
     * @return array
     */
    private function getRequestBody(): array
    {
        $coreRequest = $this->settings->getCoreRequest();

        return [
            self::WALLET      => $this->settings->getMerchantParams()->getWalletId(),
            self::DESTINATION => $this->settings->getDestinationsId(),
            self::EXTERNAL_ID => (string)$coreRequest->getOperationId(),
            self::BODY_GROUP  => [
                self::AMOUNT   => $coreRequest->getAmount(),
                self::CURRENCY => $coreRequest->getCurrency(),
            ]
        ];
    }

    /**
     * @return void
     */
    protected function createCoreOutputHandler(): void
    {
        $this->coreOutputHandler = new CoreOutputHandler(
            $this->settings->getResponseToCore(),
            $this->flow,
            $this->getDefaultCheckStatusData()
        );
    }
}
