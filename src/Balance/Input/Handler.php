<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Input;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractInputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\NotContainAvailable;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;

class Handler extends AbstractInputHandler
{
    /**
     * @var array
     */
    private $responseBody;

    /**
     * @var string
     */
    private $isValidResponse;

    /**
     * @param array $balanceData
     * @param bool  $isValidResponse
     */
    public function __construct(array $balanceData, bool $isValidResponse = true)
    {
        parent::__construct();

        $this->isValidResponse = $isValidResponse;
        $this->responseBody = $balanceData;
    }

    /**
     * @return void
     * @throws MalformedResponseException
     * @throws NotContainAvailable
     */
    public function handle(): void
    {
        $this->setParameters((new Parameters($this->responseBody)));
        $this->validate();
    }

    /**
     * @return void
     *
     * @throws NotContainAvailable
     * @throws MalformedResponseException
     */
    public function validate(): void
    {
        if ($this->isValidResponse) {
            $this->validateSuccessResponse();
        } else {
            $this->validateMalformedResponse();
        }
    }

    /**
     * @return void
     *
     * @throws NotContainAvailable
     * @throws MalformedResponseException
     */
    private function validateSuccessResponse(): void
    {
        $parameters = $this->parameters->getAll();
        $this->validator->validateTypeRequired(
            $parameters,
            [
                Parameters::AVAILABLE_GROUP => Assert::assertNotBlankArray(),
            ],
            null,
            NotContainAvailable::class
        );

        $this->validator->validateTypeRequired(
            $this->parameters->get(Parameters::AVAILABLE_GROUP),
            [
                Parameters::AVAILABLE_AMOUNT   => Assert::assertNotBlankInt(),
                Parameters::AVAILABLE_CURRENCY => Assert::assertNotBlankCurrency(),
            ],
            null,
            MalformedResponseException::class
        );
    }

    /**
     * @return void
     *
     * @throws MalformedResponseException
     */
    private function validateMalformedResponse(): void
    {
        $parameters = $this->parameters->getAll();

        $this->validator->validateTypeRequired(
            $parameters,
            [
                AbstractParameters::ERROR_TYPE => Assert::assertNotBlankString()
            ],
            null,
            MalformedResponseException::class
        );

        $this->validator->validateType(
            $parameters,
            [
                AbstractParameters::ERROR_NAME        => Assert::assertString(),
                AbstractParameters::ERROR_DESCRIPTION => Assert::assertString(),
            ],
            null,
            MalformedResponseException::class
        );
    }
}
