<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

abstract class AbstractHandler
{
    /**
     * @var AbstractParameters
     */
    protected $parameters;

    /**
     * @return mixed
     */
    abstract public function handle();

    /**
     * @param AbstractParameters $parameters
     *
     * @return $this
     */
    public function setParameters(AbstractParameters $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return AbstractParameters
     */
    public function getParameters(): AbstractParameters
    {
        return $this->parameters;
    }
}
