<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

use Closure;

class Scenario
{
    /** @var string */
    private $name;

    /** @var Closure function with condition to define current scenario */
    private $conditionFunction;

    /** @var Closure */
    private $handleFunction;

    /** @var string|null */
    private $transitionStageName;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Scenario
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConditionFunction(): ?Closure
    {
        return $this->conditionFunction;
    }

    /**
     * @param mixed $conditionFunction
     */
    public function setConditionFunction($conditionFunction): self
    {
        $this->conditionFunction = $conditionFunction;

        return $this;
    }

    /**
     * @param mixed $processFunction
     *
     * @return Scenario
     */
    public function setHandleFunction(Closure $processFunction): self
    {
        $this->handleFunction = $processFunction;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHandleFunction(): ?Closure
    {
        return $this->handleFunction;
    }

    /**
     * @param string|null $transitionStageName
     *
     * @return $this
     */
    public function setTransitionStageName(?string $transitionStageName): self
    {
        $this->transitionStageName = $transitionStageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransitionStageName(): string
    {
        return $this->transitionStageName ?? AbstractFlow::STAGE_RESPONSE_TO_CORE;
    }
}
