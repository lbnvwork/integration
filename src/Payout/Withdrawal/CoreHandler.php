<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Withdrawal;

use Closure;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class CoreHandler extends AbstractPayoutCoreHandler
{
    public const
        FAILED = '3a',
        ERROR_400_401 = '3b_400_401',
        ERROR_409_422 = '3b_409_422',
        INVALID_AMOUNT_OR_CURRENCY = '3c',
        SUCCESS = '3d',
        MALFORMED = '3e_malformed',
        NETWORK_ERROR = '3e_network_error',
        PENDING = '3f';

    /**
     * @return void
     */
    protected function buildScenarios(): void
    {
        $stage = $this->flow->getCurrentStage();

        $stage->addScenario(self::FAILED)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_ACCEPTED
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_FAILED;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleFailed']));

        $stage->addScenario(self::ERROR_400_401)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_BAD_REQUEST
                    || $this->responseStatusCode === Response::HTTP_UNAUTHORIZED;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleBadResponseError']));

        $stage->addScenario(self::ERROR_409_422)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_CONFLICT
                    || $this->responseStatusCode === Response::HTTP_UNPROCESSABLE_ENTITY;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleError409or422']));

        $stage->addScenario(self::INVALID_AMOUNT_OR_CURRENCY)
            ->setHandleFunction(function (): void {
                $withdrawalId = $this->psParams->getWithdrawalId();
                $this->flow->getSettings()->setWithdrawalId($withdrawalId);
                $this->handleInvalidAmountAndCurrency();
                $this->handleCheckStatusData($withdrawalId);
            });

        $stage->addScenario(self::SUCCESS)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_ACCEPTED
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_SUCCEEDED;
            })
            ->setHandleFunction(function (): void {
                $withdrawalId = $this->psParams->getWithdrawalId();
                $this->flow->getSettings()->setWithdrawalId($withdrawalId);
                $this->handleSuccess();
                $this->handleCheckStatusData($withdrawalId);
            });

        $stage->addScenario(self::NETWORK_ERROR)
            ->setHandleFunction(function (): void {
                $this->handleNetworkError(ServiceResponse::TYPE_FAILURE);
            });

        $stage->addScenario(self::MALFORMED)
            ->setHandleFunction(function (): void {
                $this->handleMalformedError(ServiceResponse::TYPE_FAILURE);
            });

        $stage->addScenario(self::PENDING)
            ->setConditionFunction(function (): bool {
                return $this->responseStatusCode === Response::HTTP_ACCEPTED
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_PENDING;
            })
            ->setHandleFunction(function (): void {
                $withdrawalId = $this->psParams->getWithdrawalId();
                $this->flow->getSettings()->setWithdrawalId($withdrawalId);
                $this->handleWaiting();
                $this->handleCheckStatusData($withdrawalId);
            })
            ->setTransitionStageName(Flow::STAGE_WITHDRAWAL_CHECK_REGISTRATION);

        $stage->addScenario(Flow::INVALID_RESPONSE_CODE_SCENARIO_NAME)
            ->setHandleFunction(function (): void {
                $this->handleInvalidResponseCode(ServiceResponse::TYPE_FAILURE);
            });
    }
}
