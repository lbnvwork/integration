<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Withdrawal;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\InvalidAmountOrCurrencyException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedWithValidIdException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\AbstractPsResponseHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class PsResponseHandler extends AbstractPsResponseHandler
{
    /** @var int */
    private $amount;

    /**
     * @param string $responseStatusCode
     * @param array  $responseBody
     * @param int    $amount
     * @param string $currency
     * @param int    $operationId
     */
    public function __construct(
        string $responseStatusCode,
        array $responseBody,
        int $amount,
        string $currency,
        int $operationId
    ) {
        parent::__construct($responseBody, $responseStatusCode, $operationId, $currency);
        $this->amount = $amount;
    }

    /**
     * @inheritDoc
     *
     * @throws MalformedResponseException
     * @throws InvalidAmountOrCurrencyException
     * @throws MalformedWithValidIdException
     */
    public function validate(): void
    {
        switch ($this->responseStatusCode) {
            case Response::HTTP_ACCEPTED:
                $this->validator->validateTypeRequired(
                    $this->inputData,
                    [
                        PsParams::WITHDRAWAL_ID => Assert::assertNotBlankString(),
                    ],
                    null,
                    MalformedResponseException::class
                );
                $this->validateWithdrawalSuccessData(MalformedWithValidIdException::class);
                $this->validateAmountAndCurrencyForEmpty(MalformedWithValidIdException::class);
                $this->validateAmountAndCurrency($this->amount);
                break;
            case Response::HTTP_BAD_REQUEST:
            case Response::HTTP_UNAUTHORIZED:
                $this->validateBadRequestData();
                break;
            case Response::HTTP_CONFLICT:
                $this->validateHttpConflictData();
                break;
            case Response::HTTP_UNPROCESSABLE_ENTITY:
                $this->validateUnprocessableEntityData();
                break;
            default:
                //do noting: check invalid http response code in processor
        }
    }
}
