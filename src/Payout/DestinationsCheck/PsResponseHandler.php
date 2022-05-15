<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\AbstractPsResponseHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class PsResponseHandler extends AbstractPsResponseHandler
{
    /** @var string */
    private $destinationsId;

    /**
     * @param string $responseStatusCode
     * @param array  $responseBody
     * @param string $destinationsId
     * @param int    $operationId
     * @param string $currency
     */
    public function __construct(
        string $responseStatusCode,
        array $responseBody,
        int $operationId,
        string $currency,
        string $destinationsId
    ) {
        parent::__construct($responseBody, $responseStatusCode, $operationId, $currency);
        $this->destinationsId = $destinationsId;
    }

    /**
     * @return void
     *
     * @throws MalformedResponseException
     */
    public function validate(): void
    {
        switch ($this->responseStatusCode) {
            case Response::HTTP_OK:
                $parameters = $this->parameters->getAll();
                $this->validator->validateTypeRequired(
                    $parameters,
                    [
                        PsParams::DESTINATION_ID => Assert::assertEqualTo(
                            $this->destinationsId,
                            self::DESTINATIONS_ID_VALIDATION_MESSAGE
                        ),
                    ],
                    null,
                    MalformedResponseException::class
                );
                $this->validateSuccessDestinationsData();
                break;
            case Response::HTTP_BAD_REQUEST:
                $this->validateBadRequestData();
                break;
            case Response::HTTP_UNAUTHORIZED:
            case Response::HTTP_NOT_FOUND:
                break;
            default:
                //do noting: check invalid http response code in processor
        }
    }
}
