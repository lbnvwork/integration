<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

abstract class AbstractFlow
{
    //common stages
    public const
        STAGE_CORE_REQUEST = 'core_request',
        STAGE_RESPONSE_TO_CORE = 'response_to_core';

    /** @var Stage */
    private $currentStage;

    /** @var string|null */
    private $currentScenarioName;

    /** @var AbstractSettings */
    protected $settings;

    /** @var StageCollection */
    protected $stageCollection;

    /**
     * @param AbstractSettings $settings
     */
    public function __construct(AbstractSettings $settings)
    {
        $this->settings = $settings;
        $this->stageCollection = new StageCollection([]);
        $this->buildFlow();
    }

    /**
     * @return void
     */
    abstract public function move(): void;

    /**
     * @return Stage
     */
    public function getCurrentStage(): Stage
    {
        return $this->currentStage;
    }

    /**
     * @param string $stage
     *
     * @return $this
     */
    public function setCurrentStage(string $stage): self
    {
        $this->currentStage = $this->stageCollection->get($stage);
        $this->currentScenarioName = null;

        return $this;
    }

    /**
     * @return Scenario|null
     */
    public function getCurrentScenarioName(): ?string
    {
        return $this->currentScenarioName;
    }

    /**
     * @param string $scenario
     *
     * @return $this
     */
    public function setCurrentScenarioName(string $scenario): self
    {
        $this->currentScenarioName = $scenario;

        return $this;
    }

    /**
     * @return void
     */
    abstract protected function buildFlow(): void;

    /**
     * @param string $stageName
     *
     * @return Stage
     */
    protected function addStage(string $stageName): Stage
    {
        $stage = (new Stage())->setName($stageName);
        $this->stageCollection->set($stageName, $stage);

        return $stage;
    }
}
