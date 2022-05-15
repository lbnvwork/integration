<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;

class PsParams extends AbstractParameters
{
    // common parameters
    public const
        STATUS = 'status',
        EXTERNAL_ID = 'externalID';

    //destinationsParameters
    public const
        IDENTITY = 'identity',
        CURRENCY = 'currency',
        RESOURCE_TYPE = 'type',
        RESOURCE_ACCOUNT_NUMBER = 'accountNumber',
        RESOURCE_ACCOUNT_NAME = 'accountName',
        RESOURCE_ACCOUNT_EMAIL = 'accountEmail',
        DESTINATION_ID = 'id',
        STATUS_VALUE_AUTHORIZED = 'Authorized',
        STATUS_VALUE_UNAUTHORIZED = 'Unauthorized',
        NAME = 'name',
        RESOURCE_GROUP = 'resource',
        RESOURCE_TYPE_VALUE = 'BankTransferSEPA';

    //init parameters
    public const
        ERROR_ID = 'id',
        ERROR_MESSAGE = 'message';

    //withdrawal parameters
    public const
        WALLET = 'wallet',
        DESTINATION = 'destination',
        BODY_AMOUNT = 'amount',
        BODY_CURRENCY = 'currency',
        FAILURE_CODE = 'code',
        WITHDRAWAL_ID = 'id',
        BODY_GROUP = 'body',
        FAILURE_GROUP = 'failure',
        STATUS_VALUE_SUCCEEDED = 'Succeeded',
        STATUS_VALUE_FAILED = 'Failed',
        STATUS_VALUE_PENDING = 'Pending';

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->get(self::STATUS);
    }

    /**
     * @return string
     */
    public function getDestinationsId(): string
    {
        return $this->get(self::DESTINATION_ID);
    }

    /**
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->get(self::ERROR_TYPE);
    }

    /**
     * @return string|null
     */
    public function getErrorDescription(): ?string
    {
        return $this->get(self::ERROR_DESCRIPTION);
    }

    /**
     * @return string
     */
    public function getBodyAmount(): string
    {
        return $this->get(self::BODY_AMOUNT, self::BODY_GROUP);
    }

    /**
     * @return string
     */
    public function getBodyCurrency(): string
    {
        return $this->get(self::BODY_CURRENCY, self::BODY_GROUP);
    }

    /**
     * @return string
     */
    public function getFailureCode(): string
    {
        return $this->get(self::FAILURE_CODE, self::FAILURE_GROUP);
    }

    /**
     * @return string
     */
    public function getWithdrawalId(): string
    {
        return $this->get(self::WITHDRAWAL_ID);
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->get(self::ERROR_MESSAGE);
    }
}
