<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck;

use Exception;
use Plus\Common\Interfaces\Response\Code;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\EventSystem\Event\OperationalEvent\CheckStatus\PaymentSystem\WithAutoFailure;
use Plus\PaymentSystem\ExternalData\SyntheticExternalData;
use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings;
use Plus\PaymentSystem\Response;
use Plus\Proxy;
use Plus\PaymentSystem\Interfaces\GateSettings as IGateSettings;

class CheckStatusEvent extends WithAutoFailure
{
    /** @var string */
    protected $withdrawalId;

    /** @var IRequest */
    private $coreRequest;

    /**
     * @param IRequest      $coreRequest
     * @param IGateSettings $gateSettings
     * @param string        $withdrawalId
     */
    public function __construct(IRequest $coreRequest, IGateSettings $gateSettings, string $withdrawalId)
    {
        parent::__construct($coreRequest->getOperationId(), $gateSettings);
        $this->coreRequest = $this->sanitizeRequest($coreRequest);
        $this->withdrawalId = $withdrawalId;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function checkStatusProcess(): void
    {
        $settings = new Settings($this->coreRequest, $this->gateSettings);
        $settings->setWithdrawalId($this->withdrawalId);

        $flow = new Flow($settings);
        $flow->setCurrentStage(Flow::STAGE_WITHDRAWAL_CHECK)->move();

        if ($flow->getCurrentStage()->getName() === Flow::STAGE_WITHDRAWAL_CHECK) {
            $this->registerReCall();
        } else {
            Proxy::init()->getGateCallback()->run($settings->getResponseToCore(), $this->gateSettings);
        }
    }

    /**
     * @return ServiceResponse
     */
    protected function prepareAutoResponse(): ServiceResponse
    {
        return
            (new Response())
                ->setType(ServiceResponse::TYPE_FAILURE)
                ->setCode(Code::AUTO)
                ->setOperationId($this->operationId)
                ->setExternalData(new SyntheticExternalData(Code::AUTO))
                ->setRequestType(IRequest::TYPE_PAYOUT);
    }
}
