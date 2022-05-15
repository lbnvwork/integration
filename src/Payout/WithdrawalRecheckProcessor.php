<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Plus\PaymentSystem\Interfaces\CheckStatusData as ICheckStatusData;
use Plus\PaymentSystem\Interfaces\GateSettings as IGateSettings;
use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\CheckStatusEvent as WithdrawalCheckEvent;
use Plus\Validator\Exception\DefaultValidatorException;

class WithdrawalRecheckProcessor
{
    private const DEFAULT_VALIDATOR_EXCEPTION = 'Invalid check status type [SepaViaGeDetails]';

    /** @var IRequest\WithOperationData */
    private $request;

    /** @var IGateSettings */
    private $gateSettings;

    /**
     * @param IRequest\WithOperationData $request
     * @param IGateSettings              $gateSettings
     */
    public function __construct(IRequest\WithOperationData $request, IGateSettings $gateSettings)
    {
        $this->request = $request;
        $this->gateSettings = $gateSettings;
    }

    /**
     * @throws DefaultValidatorException
     */
    public function process(): void
    {
        $data = $this->request->getOperationData()->getData();
        $withdrawalId = $data[ICheckStatusData::FIELD_EXTERNAL_DATA][AbstractPayoutCoreHandler::CHECK_STATUS_DATA_EXTERNAL_ID_NAME];
        $coreRequestType = $data[ICheckStatusData::FIELD_OPERATION_TYPE] ?? '';

        if ($coreRequestType !== IRequest::TYPE_PAYOUT) {
            throw new DefaultValidatorException(self::DEFAULT_VALIDATOR_EXCEPTION);
        }

        (new WithdrawalCheckEvent($this->request, $this->gateSettings, $withdrawalId))
            ->setOnceExecution()
            ->register();
    }
}
