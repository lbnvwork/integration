<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\InvalidAmountOrCurrencyException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\AbstractPsResponseHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class PsResponseHandler extends AbstractPsResponseHandler
{
    /** @var string */
    private $withdrawalId;

    /** @var int */
    private $amount;

    /**
     * @param array  $responseBody
     * @param string $responseStatusCodeCode
     * @param string $withdrawalId
     * @param int    $operationId
     * @param int    $amount
     * @param string $currency
     */
    public function __construct(
        array $responseBody,
        string $responseStatusCodeCode,
        int $operationId,
        int $amount,
        string $currency,
        string $withdrawalId
    ) {
        parent::__construct($responseBody, $responseStatusCodeCode, $operationId, $currency);
        $this->withdrawalId = $withdrawalId;
        $this->amount = $amount;
    }

    /**
     * @return void
     *
     * @throws MalformedResponseException
     * @throws InvalidAmountOrCurrencyException
     */
    public function validate(): void
    {
        switch ($this->responseStatusCode) {
            case Response::HTTP_OK:
                $this->validator->validateTypeRequired(
                    $this->inputData,
                    [
                        PsParams::WITHDRAWAL_ID => Assert::assertEqualTo(
                            $this->withdrawalId,
                            self::WITHDRAWAL_ID_VALIDATION_MESSAGE
                        ),
                    ],
                    null,
                    MalformedResponseException::class
                );
                $this->validateWithdrawalSuccessData(MalformedResponseException::class);
                $this->validateAmountAndCurrencyForEmpty(MalformedResponseException::class);
                $this->validateAmountAndCurrency($this->amount);
                break;
            case Response::HTTP_BAD_REQUEST:
            case Response::HTTP_UNAUTHORIZED:
            case Response::HTTP_NOT_FOUND:
                $this->validateBadRequestData();
                break;
            default:
                //do noting: check invalid http response code in processor
        }
    }
}
