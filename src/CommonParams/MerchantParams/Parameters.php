<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\MerchantParams;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;

class Parameters extends AbstractParameters
{
    public const
        NAME = 'name',
        IDENTITY = 'identity',
        WALLET_ID = 'wallet_id',
        API_KEY = 'APIKey',
        PAYOUT_CREATE_URL = 'payout_create_url',
        PAYOUT_CHECK_CREATE_URL = 'payout_check_create_url',
        PAYOUT_URL_COMPLETE = 'payout_url_complete',
        BALANCE_URL = 'balance_url',
        CHECK_STATUS_URL = 'check_status_url',
        MIN_PAYOUT_AMOUNT = 'min_payout_amount',
        MAX_PAYOUT_AMOUNT = 'max_payout_amount',
        CHECK_STATUS_LIFETIME = 'check_status_lifetime',
        DESTINATIONS_CHECK_STATUS_LIFETIME = 'destination_lifetime';

    /**
     * @return int
     */
    public function getCheckStatusLifetime(): int
    {
        return $this->get(self::CHECK_STATUS_LIFETIME);
    }

    /**
     * @return string
     */
    public function getDestinationsUrl(): string
    {
        return $this->get(self::PAYOUT_CREATE_URL);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->get(self::NAME);
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return $this->get(self::IDENTITY);
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->get(self::API_KEY);
    }

    /**
     * @return string
     */
    public function getDestinationsCheckUrlTemplate(): string
    {
        return $this->get(self::PAYOUT_CHECK_CREATE_URL);
    }

    /**
     * @return string
     */
    public function getWithdrawalUrlTemplate(): string
    {
        return $this->get(self::PAYOUT_URL_COMPLETE);
    }

    /**
     * @return string
     */
    public function getWalletId(): string
    {
        return $this->get(self::WALLET_ID);
    }

    /**
     * @return string
     */
    public function getWithdrawalCheckUrlTemplate(): string
    {
        return $this->get(self::CHECK_STATUS_URL);
    }

    /**
     * @return string
     */
    public function getBalanceUrlTemplate(): string
    {
        return $this->get(self::BALANCE_URL);
    }

    /**
     * @return string
     */
    public function getDestinationsCheckStatusLifetime(): string
    {
        return $this->get(self::DESTINATIONS_CHECK_STATUS_LIFETIME);
    }
}
