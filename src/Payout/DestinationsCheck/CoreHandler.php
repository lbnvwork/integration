<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck;

use Closure;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class CoreHandler extends AbstractPayoutCoreHandler
{
    //stage destinations check scenarios
    public const
        AUTHORIZED = '2a',
        ERROR_400 = '2b_400',
        ERROR_401_404 = '2b_401_404',
        MALFORMED = '2c',
        NETWORK_ERROR = '2d',
        UNAUTHORIZED = '2e';

    /**
     * @return void
     */
    protected function buildScenarios(): void
    {
        $stage = $this->flow->getCurrentStage();

        $stage->addScenario(self::AUTHORIZED)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_OK
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_AUTHORIZED;
            })
            ->setTransitionStageName(Flow::STAGE_WITHDRAWAL);

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

        $stage->addScenario(self::MALFORMED)
            ->setHandleFunction(Closure::fromCallable([$this, 'handleMalformedError']));

        $stage->addScenario(self::NETWORK_ERROR)
            ->setHandleFunction(Closure::fromCallable([$this, 'handleNetworkError']));

        $stage->addScenario(self::UNAUTHORIZED)
            ->setConditionFunction(function (): bool {
                return
                    $this->responseStatusCode === Response::HTTP_OK
                    && $this->psParams->getStatus() === PsParams::STATUS_VALUE_UNAUTHORIZED;
            })
            ->setTransitionStageName(Flow::STAGE_DESTINATIONS_CHECK);

        $stage->addScenario(Flow::INVALID_RESPONSE_CODE_SCENARIO_NAME)
            ->setHandleFunction(function (): void {
                $this->handleInvalidResponseCode();
            });
    }
}
