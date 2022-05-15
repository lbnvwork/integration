<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

use Plus\Proxy;
use Plus\Validator;

abstract class AbstractInputHandler extends AbstractHandler
{
    protected const
        DESTINATIONS_ID_VALIDATION_MESSAGE = 'This value should be equal to [destinations_id] parameter.',
        EXTERNAL_ID_VALIDATION_MESSAGE = 'This value should be equal to [operation_id] parameter.',
        WITHDRAWAL_ID_VALIDATION_MESSAGE = 'This value should be equal to [withdrawal_id] parameter.';

    public const
        DESCRIPTION_DEFAULT_VALUE = 'DEFAULT_ERROR',
        INVALID_HTTP_RESPONSE_STATUS_CODE_ERROR = 'Invalid response status code: ';

    /** @var array */
    protected $inputData;

    /** @var Validator */
    protected $validator;

    public function __construct()
    {
        $this->validator = Proxy::init()->getValidator();
    }

    /**
     * @return void
     */
    abstract public function handle(): void;

    /**
     * @return void
     */
    abstract public function validate(): void;
}
