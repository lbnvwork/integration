<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Input;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;

class Parameters extends AbstractParameters
{
    public const
        AVAILABLE_GROUP = 'available',
        AVAILABLE_AMOUNT = 'amount',
        AVAILABLE_CURRENCY = 'currency';

    public const
        DEFAULT_ERROR_VALUE = 'DEFAULT_ERROR';

    /**
     * @return string
     */
    public function getAvailableAmount(): string
    {
        return $this->get(self::AVAILABLE_AMOUNT, self::AVAILABLE_GROUP);
    }

    /**
     * @return string
     */
    public function getAvailableCurrency(): string
    {
        return $this->get(self::AVAILABLE_CURRENCY, self::AVAILABLE_GROUP);
    }

    /**
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->get(self::ERROR_TYPE);
    }

    /**
     * @return string
     */
    public function getErrorDescription(): string
    {
        return $this->get(self::ERROR_DESCRIPTION) ?? self::DEFAULT_ERROR_VALUE;
    }
}
