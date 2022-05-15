<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Closure;
use Exception;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractFlow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\Scenario;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Destinations\Processor as DestinationsProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck\CheckStatusEvent as DestinationsCheckEvent;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck\Processor as DestinationsCheckProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Withdrawal\Processor as WithdrawalProcessor;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\CheckStatusEvent as WithdrawalCheckEvent;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\Processor as WithdrawalCheckProcessor;

class Flow extends AbstractFlow
{
    public const INVALID_RESPONSE_CODE_SCENARIO_NAME = 'invalid_response_code';

    /** @var Settings */
    protected $settings;

    //payout flow stages
    public const
        STAGE_DESTINATIONS = 'destinations',
        STAGE_DESTINATIONS_CHECK_REGISTRATION = 'destinations_check_registration',
        STAGE_DESTINATIONS_CHECK = 'destinations_check',
        STAGE_WITHDRAWAL = 'withdrawal',
        STAGE_WITHDRAWAL_CHECK_REGISTRATION = 'withdrawal_check_registration',
        STAGE_WITHDRAWAL_CHECK = 'withdrawal_check';

    /**
     * @return void
     * @throws Exception
     */
    public function move(): void
    {
        $currentStage = $this->getCurrentStage();
        $process = $currentStage->getProcessFunction();
        if (is_callable($process)) {
            $process();
        }
    }

    /**
     * @return void
     */
    protected function buildFlow(): void
    {
        $this->addStage(self::STAGE_CORE_REQUEST);
        $this->addStage(self::STAGE_DESTINATIONS)
            ->setProcessFunction(
                Closure::fromCallable([$this, 'processDestinations'])
            );
        $this->addStage(self::STAGE_DESTINATIONS_CHECK_REGISTRATION)
            ->setProcessFunction(
                Closure::fromCallable([$this, 'processDestinationsCheckRegistration'])
            );
        $this->addStage(self::STAGE_DESTINATIONS_CHECK)
            ->setProcessFunction(
                Closure::fromCallable([$this, 'processDestinationsCheck'])
            );
        $this->addStage(self::STAGE_WITHDRAWAL)
            ->setProcessFunction(
                Closure::fromCallable([$this, 'processWithdrawal'])
            );
        $this->addStage(self::STAGE_WITHDRAWAL_CHECK_REGISTRATION)
            ->setProcessFunction(
                Closure::fromCallable([$this, 'processWithdrawalCheckRegistration'])
            );
        $this->addStage(self::STAGE_WITHDRAWAL_CHECK)
            ->setProcessFunction(
                Closure::fromCallable([$this, 'processWithdrawalCheck'])
            );
        $this->addStage(self::STAGE_RESPONSE_TO_CORE);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function processDestinations(): void
    {
        (new DestinationsProcessor($this->settings, $this))->process();
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function processDestinationsCheckRegistration(): void
    {
        $destinationsCheckEvent = new DestinationsCheckEvent(
            $this->settings->getCoreRequest(),
            $this->settings->getGateSettings(),
            $this->settings->getDestinationsId()
        );

        $destinationsCheckEvent
            ->setTimeToCall($destinationsCheckEvent->prepareTimeToCall())
            ->setActivePeriod($this->settings->getMerchantParams()->getDestinationsCheckStatusLifetime())
            ->register();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function processDestinationsCheck(): void
    {
        (new DestinationsCheckProcessor($this->settings, $this))->process();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function processWithdrawal(): void
    {
        $this->settings->setIsFinRisk(true);
        (new WithdrawalProcessor($this->settings, $this))->process();
        $this->settings->setIsFinRisk(false);
    }

    /**
     * @return void
     */
    public function processWithdrawalCheckRegistration(): void
    {
        (new WithdrawalCheckEvent(
            $this->settings->getCoreRequest(),
            $this->settings->getGateSettings(),
            $this->settings->getWithdrawalId()
        ))
            ->setActivePeriod($this->settings->getMerchantParams()->getCheckStatusLifetime())
            ->register();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function processWithdrawalCheck(): void
    {
        (new WithdrawalCheckProcessor($this->settings, $this))->process();
    }

    /**
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }

    /**
     * @return Scenario
     */
    public function defineScenario(): Scenario
    {
        $scenarioCollection = $this->getCurrentStage()->getScenarioCollection();

        $currentScenarioName = $this->getCurrentScenarioName();
        if ($currentScenarioName) {
            return $scenarioCollection->get($currentScenarioName);
        }

        /** @var Scenario $scenario */
        foreach ($scenarioCollection->getAll() as $scenario) {
            $condition = $scenario->getConditionFunction();
            if (is_callable($condition) && $condition()) {
                return $scenario;
            }
        }

        return $scenarioCollection->get(self::INVALID_RESPONSE_CODE_SCENARIO_NAME);
    }
}
