<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Input\Parameters as PsParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Output\CoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractFlow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractSettings;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\NotContainAvailable;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Processor extends AbstractProcessor
{
    /** @var CoreHandler */
    protected $coreOutputHandler;

    /**
     * @param AbstractSettings $settings
     * @param AbstractFlow     $flow
     */
    public function __construct(AbstractSettings $settings, AbstractFlow $flow)
    {
        parent::__construct($settings, $flow);
        $this->createCoreOutputHandler();
    }

    /**
     * @return void
     */
    public function process(): void
    {
        $isResponseSuccess = true;

        try {
            $httpResponse = $this->doBalanceRequest();
        } catch (ClientException $e) {
            $httpResponse = $e->getResponse();
            $isResponseSuccess = false;
        } catch (RequestException $e) {
            $this->flow->setCurrentScenarioName(AbstractCoreHandler::NETWORK_ERROR);
            $this->coreOutputHandler->setResponseExceptionMessage($this->getExceptionMessage($e));
            $this->coreOutputHandler->handle();

            return;
        }

        $this->checkAndResolvePsResponse($httpResponse, $isResponseSuccess);
    }

    /**
     * @return ResponseInterface
     */
    private function doBalanceRequest(): ResponseInterface
    {
        $merchantParams = $this->settings->getMerchantParams();
        $headers = $this->settings->getHeaders();
        $walletId = $merchantParams->getWalletId();

        return $this->sendRequest(
            $headers->getUrlByTemplate(
                $merchantParams->getBalanceUrlTemplate(),
                $walletId
            ),
            $headers->getAll(),
            [],
            $headers->getMaskedFields(),
            HttpRequest::METHOD_GET
        );
    }

    /**
     * @param ResponseInterface $httpResponse
     * @param bool              $isResponseSuccess
     *
     * @return void
     */
    private function checkAndResolvePsResponse(ResponseInterface $httpResponse, bool $isResponseSuccess): void
    {
        try {
            $psResponseHandler = new Input\Handler($this->parseJson($httpResponse->getBody()), $isResponseSuccess);
            $psResponseHandler->handle();
            /** @var PsParams $psParams */
            $psParams = $psResponseHandler->getParameters();
            $this->coreOutputHandler->setPsParams($psParams);
            if ($isResponseSuccess) {
                $this->flow->setCurrentScenarioName(CoreHandler::SUCCESS);
            } else {
                $this->flow->setCurrentScenarioName(CoreHandler::CLIENT_EXCEPTION);
            }
        } catch (NotContainAvailable $e) {
            $this->flow->setCurrentScenarioName(CoreHandler::NOT_EQUAL_AVAILABLE);
            $this->coreOutputHandler->setResponseExceptionMessage($e->getMessage());
        } catch (MalformedResponseException $e) {
            $this->flow->setCurrentScenarioName(AbstractCoreHandler::MALFORMED);
            $this->coreOutputHandler->setResponseExceptionMessage($e->getMessage());
        }
        $this->coreOutputHandler->handle();
    }

    /**
     * @return void
     */
    protected function createCoreOutputHandler(): void
    {
        $this->coreOutputHandler = new CoreHandler($this->settings->getResponseToCore(), $this->flow);
    }
}
