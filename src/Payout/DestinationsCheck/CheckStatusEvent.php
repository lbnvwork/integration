<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck;

use DateInterval;
use DateTime;
use Exception;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\EventSystem\Event\OperationalEvent\CheckStatus\PaymentSystem\WithAutoDecline;
use Plus\PaymentSystem\Interfaces\GateSettings as IGateSettings;
use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\Interfaces\Response as IResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings;
use Plus\Proxy;

class CheckStatusEvent extends WithAutoDecline
{
    //check status event parameters
    private const CHECK_STATUS_INTERVAL_SEC = 5;

    /** @var string */
    private $destinationsId;

    /**
     * @var IRequest
     */
    private $coreRequest;

    /**
     * @param IRequest      $coreRequest
     * @param IGateSettings $gateSettings
     * @param string        $destinationsId
     */
    public function __construct(IRequest $coreRequest, IGateSettings $gateSettings, string $destinationsId)
    {
        parent::__construct($coreRequest->getOperationId(), $gateSettings);
        $this->destinationsId = $destinationsId;
        $this->coreRequest = $this->sanitizeRequest($coreRequest);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function checkStatusProcess(): void
    {
        $settings = new Settings($this->coreRequest, $this->gateSettings);
        $settings->setDestinationsId($this->destinationsId);

        $flow = new Flow($settings);
        $flow->setCurrentStage(Flow::STAGE_DESTINATIONS_CHECK)->move();

        $responseToCore = $settings->getResponseToCore();

        switch ($flow->getCurrentStage()->getName()) {
            case Flow::STAGE_DESTINATIONS_CHECK:
                $this->registerReCall();
                break;
            case Flow::STAGE_WITHDRAWAL:
                $flow->move();
                $this->runCallback($responseToCore);
                break;
            default:
                $this->runCallback($responseToCore);
        }
    }

    /**
     * @param IResponse $responseToCore
     *
     * @return void
     */
    private function runCallback(IResponse $responseToCore): void
    {
        Proxy::init()->getGateCallback()->run($responseToCore, $this->gateSettings);
    }

    /**
     * @return DateTime
     *
     * @throws Exception
     */
    public function prepareTimeToCall(): DateTime
    {
        return (new DateTime())->add(new DateInterval('PT' . self::CHECK_STATUS_INTERVAL_SEC . 'S'));
    }

    /**
     * @return ServiceResponse
     */
    protected function prepareAutoResponse(): ServiceResponse
    {
        $response = parent::prepareAutoResponse();

        return $response->setRequestType(IRequest::TYPE_PAYOUT);
    }
}
