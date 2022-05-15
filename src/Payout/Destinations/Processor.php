<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Destinations;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractFlow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Input\ClientParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Input\ClientParamsHandler as CoreInputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Destinations\CoreHandler as CoreOutputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings as PayoutSettings;

class Processor extends AbstractProcessor
{
    //output parameters
    private const
        NAME = 'name',
        IDENTITY = 'identity',
        EXTERNAL_ID = 'externalID',
        RESOURCE_ACCOUNT_NUMBER = 'accountNumber',
        RESOURCE_ACCOUNT_NAME = 'accountName',
        DP_RESOURCE_ACCOUNT_EMAIL = 'accountEmail',
        DP_CURRENCY = 'currency',
        DP_RESOURCE_TYPE = 'type',
        GROUP_RESOURCE = 'resource',
        DP_RESOURCE_TYPE_VALUE = 'BankTransferSEPA';

    /** @var ClientParams */
    private $clientParams;

    /** @var PayoutSettings */
    protected $settings;

    /** @var CoreHandler */
    protected $coreOutputHandler;

    /**
     * @param PayoutSettings $payoutSettings
     * @param AbstractFlow   $flow
     */
    public function __construct(PayoutSettings $payoutSettings, AbstractFlow $flow)
    {
        parent::__construct($payoutSettings, $flow);
        $clientParamsHandle = new CoreInputHandler($this->settings->getCoreRequest()->getClientParams());
        $clientParamsHandle->handle();
        $this->clientParams = $clientParamsHandle->getParameters();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function process(): void
    {
        try {
            $httpResponse = $this->getHttpResponse(
                $this->settings->getMerchantParams()->getDestinationsUrl(),
                $this->getRequestBody()
            );
            $coreRequest = $this->settings->getCoreRequest();

            $psResponseHandler = (new PsResponseHandler(
                $this->parseJson($httpResponse->getBody()),
                $httpResponse->getStatusCode(),
                $coreRequest->getOperationId(),
                $coreRequest->getCurrency()
            ));
            $psResponseHandler->handle();

            $this->coreOutputHandler
                ->setPsParams($psResponseHandler->getParameters())
                ->setResponseStatusCode($httpResponse->getStatusCode());
        } catch (RequestException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::NETWORK_ERROR);
            $this->coreOutputHandler->setResponseExceptionMessage($this->getExceptionMessage($exception));
        } catch (MalformedResponseException $exception) {
            $this->flow->setCurrentScenarioName(CoreOutputHandler::MALFORMED);
            $this->coreOutputHandler->setResponseExceptionMessage($exception->getMessage());
        }

        $this->coreOutputHandler->handle();
    }

    /**
     * @return array
     */
    private function getRequestBody(): array
    {
        $merchantParams = $this->settings->getMerchantParams();
        $coreRequest = $this->settings->getCoreRequest();

        return [
            self::NAME           => $merchantParams->getName(),
            self::IDENTITY       => $merchantParams->getIdentity(),
            self::EXTERNAL_ID    => (string)$coreRequest->getOperationId(),
            self::GROUP_RESOURCE => [
                self::RESOURCE_ACCOUNT_NUMBER   => $this->clientParams->getAccount(),
                self::RESOURCE_ACCOUNT_NAME     => $this->clientParams->getAccountHolder(),
                self::DP_RESOURCE_ACCOUNT_EMAIL => $this->clientParams->getEmail(),
                self::DP_RESOURCE_TYPE          => self::DP_RESOURCE_TYPE_VALUE
            ],
            self::DP_CURRENCY    => $coreRequest->getCurrency()
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
