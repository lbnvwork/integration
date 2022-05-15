<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck;

use Closure;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class CoreHandler extends AbstractPayoutCoreHandler
{
    //handle events
    public const
        FAILED = '4a',
        ERROR_400 = '4b_400',
        ERROR_401_404 = '4b_401_404',
        SUCCEEDED = '4c',
        INVALID_AMOUNT_OR_CURRENCY = '4d',
        MALFORMED = '4e_malformed',
        NETWORK_ERROR = '4e_network_error',
        PENDING = '4f';

    /**
     * @return void
     */
    protected function buildScenarios(): void
    {
        $stage = $this->flow->getCurrentStage();

        $stage->addScenario(self::FAILED)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_OK
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_FAILED;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleFailed']));

        $stage->addScenario(self::ERROR_400)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_BAD_REQUEST;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleBadResponseError']));

        $stage->addScenario(self::ERROR_401_404)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_UNAUTHORIZED
                    || $this->responseStatusCode === Response::HTTP_NOT_FOUND;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleError401or404']));

        $stage->addScenario(self::SUCCEEDED)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_OK
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_SUCCEEDED;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleSuccess']));

        $stage->addScenario(self::INVALID_AMOUNT_OR_CURRENCY)
            ->setHandleFunction(Closure::fromCallable([$this, 'handleInvalidAmountAndCurrency']));

        $stage->addScenario(self::MALFORMED)
            ->setTransitionStageName(Flow::STAGE_WITHDRAWAL_CHECK);

        $stage->addScenario(self::NETWORK_ERROR)
            ->setTransitionStageName(Flow::STAGE_WITHDRAWAL_CHECK);

        $stage->addScenario(self::PENDING)
            ->setConditionFunction(function () {
                return $this->responseStatusCode === Response::HTTP_OK
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_PENDING;
            })
            ->setTransitionStageName(Flow::STAGE_WITHDRAWAL_CHECK);

        $stage->addScenario(Flow::INVALID_RESPONSE_CODE_SCENARIO_NAME)
            ->setHandleFunction(function (): void {
                $this->handleInvalidResponseCode(ServiceResponse::TYPE_FAILURE);
            });
    }
}
