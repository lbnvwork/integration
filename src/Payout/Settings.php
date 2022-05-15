<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractSettings;

class Settings extends AbstractSettings
{
    /** @var string */
    private $withdrawalId;

    /** @var string */
    private $destinationsId;

    /**
     * @return string
     */
    public function getDestinationsId(): string
    {
        return $this->destinationsId;
    }

    /**
     * @return string
     */
    public function getWithdrawalId(): string
    {
        return $this->withdrawalId;
    }

    /**
     * @param string $destinationsId
     *
     * @return void
     */
    public function setDestinationsId(string $destinationsId): void
    {
        $this->destinationsId = $destinationsId;
    }

    /**
     * @param string $withdrawalId
     *
     * @return void
     */
    public function setWithdrawalId(string $withdrawalId): void
    {
        $this->withdrawalId = $withdrawalId;
    }
}
