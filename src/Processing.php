<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails;

use Exception;
use Plus\Common\Interfaces\Response\Code;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\PaymentSystem\Interfaces\Request;
use Plus\PaymentSystem\Interfaces\Response as IResponse;
use Plus\PaymentSystem\Processing as PaymentSystemProcessing;
use Plus\PaymentSystem\Interfaces\Processing\PaymentMethods;
use Plus\PaymentSystem\Interfaces\Processing\AdditionalMethods;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow as PayoutFlow;
use Plus\Validator\Exception\DefaultValidatorException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Settings as PayoutSettings;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Settings as BalanceSettings;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalRecheckProcessor;

class Processing extends PaymentSystemProcessing implements
    PaymentMethods\Payout,
    AdditionalMethods\MerchantBalance,
    AdditionalMethods\CheckStatus
{
    /**
     * @param Request $request
     *
     * @return IResponse
     *
     * @throws Exception
     */
    public function payout(Request $request): IResponse
    {
        $settings = new PayoutSettings($request, $this->gateSettings);
        $flow = new PayoutFlow($settings);
        $flow->setCurrentStage(PayoutFlow::STAGE_DESTINATIONS)->move();

        return $settings->getResponseToCore();
    }

    /**
     * @param Request $request
     *
     * @return IResponse
     *
     * @throws Exception
     */
    public function merchantBalance(Request $request): IResponse
    {
        $settings = new BalanceSettings($request, $this->gateSettings);
        $flow = new Balance\Flow($settings);

        (new Balance\Processor($settings, $flow))->process();

        return $settings->getResponseToCore();
    }

    /**
     * @param Request\WithOperationData $request
     *
     * @return IResponse
     *
     * @throws DefaultValidatorException
     */
    public function checkStatus(Request\WithOperationData $request): IResponse
    {
        (new WithdrawalRecheckProcessor($request, $this->gateSettings))->process();

        return $this->createBaseResponse($request)
            ->setType(ServiceResponse::TYPE_WAITING)
            ->setCode(Code::PROCESSING_ASYNC);
    }
}
