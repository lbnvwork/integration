<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

use Closure;

class Stage
{
    /** @var string */
    private $name;

    /** @var Closure|null process stage */
    public $processFunction;

    /** @var ScenarioCollection collection of scenarios for stage */
    protected $scenarioCollection;

    /**
     * @param array $scenarioArray
     */
    public function __construct(array $scenarioArray = [])
    {
        $this->scenarioCollection = new ScenarioCollection($scenarioArray);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param mixed $process
     */
    public function setProcessFunction(Closure $process): self
    {
        $this->processFunction = $process;

        return $this;
    }

    /**
     * @return Closure|null
     */
    public function getProcessFunction(): ?Closure
    {
        return $this->processFunction;
    }

    /**
     * @return ScenarioCollection
     */
    public function getScenarioCollection(): ScenarioCollection
    {
        return $this->scenarioCollection;
    }

    /**
     * @param string $scenarioName
     *
     * @return Scenario
     */
    public function addScenario(string $scenarioName): Scenario
    {
        $scenario = (new Scenario())->setName($scenarioName);
        $this->getScenarioCollection()->set($scenarioName, $scenario);

        return $scenario;
    }
}
