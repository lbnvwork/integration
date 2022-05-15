<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Destinations;

use Closure;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class CoreHandler extends AbstractPayoutCoreHandler
{
    //stage destinations scenarios
    public const
        AUTHORIZED = '1a',
        ERROR_400_401 = '1b_400_401',
        ERROR_409_422 = '1b_409_422',
        MALFORMED = '1c',
        NETWORK_ERROR = '1d',
        UNAUTHORIZED = '1e';

    /**
     * @return void
     */
    protected function buildScenarios(): void
    {
        $stage = $this->flow->getCurrentStage();

        $stage->addScenario(self::ERROR_400_401)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_BAD_REQUEST
                    || $this->responseStatusCode === Response::HTTP_UNAUTHORIZED;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleBadResponseError']));

        $stage->addScenario(self::ERROR_409_422)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_CONFLICT
                    || $this->responseStatusCode === Response::HTTP_UNPROCESSABLE_ENTITY;
            })
            ->setHandleFunction(Closure::fromCallable([$this, 'handleError409or422']));

        $stage->addScenario(self::MALFORMED)
            ->setHandleFunction(Closure::fromCallable([$this, 'handleMalformedError']));

        $stage->addScenario(self::NETWORK_ERROR)
            ->setHandleFunction(Closure::fromCallable([$this, 'handleNetworkError']));

        $stage->addScenario(self::UNAUTHORIZED)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_CREATED
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_UNAUTHORIZED;
            })
            ->setHandleFunction(function (): void {
                $destinationsId = $this->psParams->getDestinationsId();
                $this->flow->getSettings()->setDestinationsId($destinationsId);
                $this->handleWaiting();
                $this->handleCheckStatusData($destinationsId);
            })
            ->setTransitionStageName(Flow::STAGE_DESTINATIONS_CHECK_REGISTRATION);

        $stage->addScenario(self::AUTHORIZED)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_CREATED
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_AUTHORIZED;
            })
            ->setHandleFunction(function (): void {
                $this->flow->getSettings()->setDestinationsId($this->psParams->getDestinationsId());
            })
            ->setTransitionStageName(Flow::STAGE_WITHDRAWAL);

        $stage->addScenario(Flow::INVALID_RESPONSE_CODE_SCENARIO_NAME)
            ->setHandleFunction(function (): void {
                $this->handleInvalidResponseCode();
            });
    }
}
