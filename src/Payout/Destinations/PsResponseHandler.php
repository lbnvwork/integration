<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Destinations;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Exceptions\MalformedResponseException;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\AbstractPsResponseHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;
use Symfony\Component\HttpFoundation\Response;

class PsResponseHandler extends AbstractPsResponseHandler
{
    /**
     * @throws MalformedResponseException
     */
    public function validate(): void
    {
        $parameters = $this->parameters->getAll();
        switch ($this->responseStatusCode) {
            case Response::HTTP_CREATED:
                $this->validator->validateTypeRequired(
                    $parameters,
                    [
                        PsParams::DESTINATION_ID => Assert::assertNotBlankString(),
                    ],
                    null,
                    MalformedResponseException::class
                );
                $this->validateSuccessDestinationsData();
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
