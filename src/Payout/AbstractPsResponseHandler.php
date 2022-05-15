<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractInputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\InvalidAmountOrCurrencyException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;

abstract class AbstractPsResponseHandler extends AbstractInputHandler
{
    /** @var string */
    protected $responseStatusCode;

    /** @var int */
    protected $operationId;

    /** @var string */
    protected $currency;

    /**
     * @param array  $responseBody
     * @param string $responseStatusCode
     * @param int    $operationId
     * @param string $currency
     */
    public function __construct(array $responseBody, string $responseStatusCode, int $operationId, string $currency)
    {
        parent::__construct();
        $this->inputData = $responseBody;
        $this->responseStatusCode = $responseStatusCode;
        $this->operationId = $operationId;
        $this->currency = $currency;
    }

    /**
     * @return void
     * @throws MalformedResponseException
     */
    public function handle(): void
    {
        $this->setParameters(new PsParams($this->inputData));
        $this->validate();
    }

    /**
     * @return void
     */
    protected function validateSuccessDestinationsData(): void
    {
        $this->validator->validateTypeRequired(
            $this->inputData,
            [
                PsParams::STATUS      => Assert::assertChoice(
                    [
                        PsParams::STATUS_VALUE_AUTHORIZED,
                        PsParams::STATUS_VALUE_UNAUTHORIZED,
                    ]
                ),
                PsParams::EXTERNAL_ID => Assert::assertEqualTo(
                    (string)$this->operationId,
                    self::EXTERNAL_ID_VALIDATION_MESSAGE
                ),
                PsParams::CURRENCY    => Assert::assertEqualTo($this->currency)
            ],
            null,
            MalformedResponseException::class
        );

        $this->validator->validateType(
            $this->inputData,
            [
                PsParams::NAME           => Assert::assertString(),
                PsParams::IDENTITY       => Assert::assertString(),
                PsParams::RESOURCE_GROUP => Assert::assertArray(),
            ],
            null,
            MalformedResponseException::class
        );

        if ($this->parameters->isset(PsParams::RESOURCE_GROUP)) {
            $this->validator->validateType(
                $this->parameters->get(PsParams::RESOURCE_GROUP),
                [
                    PsParams::RESOURCE_TYPE           => Assert::assertEqualTo(
                        PsParams::RESOURCE_TYPE_VALUE
                    ),
                    PsParams::RESOURCE_ACCOUNT_NUMBER => Assert::assertString(),
                    PsParams::RESOURCE_ACCOUNT_NAME   => Assert::assertString(),
                    PsParams::RESOURCE_ACCOUNT_EMAIL  => Assert::assertString(),
                ],
                null,
                MalformedResponseException::class
            );
        }
    }

    /**
     * @return void
     */
    protected function validateBadRequestData(): void
    {
        $this->validator->validateTypeRequired(
            $this->inputData,
            [
                AbstractParameters::ERROR_TYPE => Assert::assertNotBlankString(),
            ],
            null,
            MalformedResponseException::class
        );

        $this->validator->validateType(
            $this->inputData,
            [
                AbstractParameters::ERROR_NAME        => Assert::assertString(),
                AbstractParameters::ERROR_DESCRIPTION => Assert::assertString(),
            ],
            null,
            MalformedResponseException::class
        );
    }

    /**
     * @return void
     */
    protected function validateHttpConflictData(): void
    {
        $this->validator->validateType(
            $this->inputData,
            [
                PsParams::EXTERNAL_ID   => Assert::assertString(),
                PsParams::ERROR_ID      => Assert::assertString(),
                PsParams::ERROR_MESSAGE => Assert::assertString(),
            ],
            null,
            MalformedResponseException::class
        );
    }

    /**
     * @return void
     */
    protected function validateUnprocessableEntityData(): void
    {
        $this->validator->validateType(
            $this->inputData,
            [
                PsParams::ERROR_MESSAGE => Assert::assertString(),
            ],
            null,
            MalformedResponseException::class
        );
    }

    /**
     * @param string $exceptionClass
     *
     * @return void
     */
    protected function validateWithdrawalSuccessData(string $exceptionClass): void
    {
        $this->validator->validateTypeRequired(
            $this->inputData,
            [
                PsParams::STATUS      => Assert::assertChoice(
                    [
                        PsParams::STATUS_VALUE_SUCCEEDED,
                        PsParams::STATUS_VALUE_FAILED,
                        PsParams::STATUS_VALUE_PENDING,
                    ]
                ),
                PsParams::EXTERNAL_ID => Assert::assertEqualTo(
                    (string)$this->operationId,
                    self::EXTERNAL_ID_VALIDATION_MESSAGE
                ),
                PsParams::BODY_GROUP  => Assert::assertNotBlankArray()
            ],
            null,
            $exceptionClass
        );

        if ($this->parameters->get(PsParams::STATUS) === PsParams::STATUS_VALUE_FAILED) {
            $this->validator->validateTypeRequired(
                $this->inputData,
                [
                    PsParams::FAILURE_GROUP => Assert::assertNotBlankArray(),
                ],
                null,
                $exceptionClass
            );
            $this->validator->validateTypeRequired(
                $this->parameters->get(PsParams::FAILURE_GROUP),
                [
                    PsParams::FAILURE_CODE => Assert::assertNotBlankString(),
                ],
                null,
                $exceptionClass
            );
        }
    }

    /**
     * @param int $amount
     *
     * @return void
     */
    protected function validateAmountAndCurrency(int $amount): void
    {
        $this->validator->validateTypeRequired(
            $this->parameters->get(PsParams::BODY_GROUP),
            [
                PsParams::BODY_AMOUNT   => Assert::assertEqualTo($amount),
                PsParams::BODY_CURRENCY => Assert::assertEqualTo($this->currency),
            ],
            null,
            InvalidAmountOrCurrencyException::class
        );
    }

    /**
     * @param string $exceptionClass
     *
     * @return void
     */
    protected function validateAmountAndCurrencyForEmpty(string $exceptionClass): void
    {
        $this->validator->validateTypeRequired(
            $this->getParameters()->get(PsParams::BODY_GROUP),
            [
                PsParams::BODY_AMOUNT   => Assert::assertNotBlankInt(),
                PsParams::BODY_CURRENCY => Assert::assertNotBlankString(),
            ],
            null,
            $exceptionClass
        );
    }
}
