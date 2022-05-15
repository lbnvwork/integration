<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Input;

use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;

class ClientParams extends AbstractParameters
{
    public const
        ACCOUNTHOLDER = 'accountholder';


    /**
     * @return string
     */
    public function getAccount(): string
    {
        return $this->get(IRequest::CP_ACCOUNT);
    }

    /**
     * @return string
     */
    public function getAccountHolder(): string
    {
        return $this->get(self::ACCOUNTHOLDER, IRequest::CP_CUSTOM);
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->get(IRequest::CP_EMAIL);
    }
}
