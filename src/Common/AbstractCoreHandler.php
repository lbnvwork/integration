<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

use Plus\Common\Interfaces\Response\Code;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\PaymentSystem\ExternalData\SyntheticExternalData;
use Plus\PaymentSystem\Interfaces\Response as IResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output\AbstractPayoutCoreHandler;

abstract class AbstractCoreHandler extends AbstractHandler
{
    //default scenarios
    public const
        NETWORK_ERROR = 'external_error',
        MALFORMED = 'malformed';

    /** @var AbstractParameters */
    protected $psParams;

    /** @var IResponse */
    protected $responseToCore;

    /** @var AbstractFlow */
    protected $flow;

    /** @var string */
    protected $responseExceptionMessage;

    /**
     * @param IResponse    $response
     * @param AbstractFlow $flow
     */
    public function __construct(IResponse $response, AbstractFlow $flow)
    {
        $this->responseToCore = $response;
        $this->flow = $flow;
    }

    /**
     * @param int         $responseCode
     * @param string|null $message
     *
     * @return void
     */
    protected function handleSyntheticData(int $responseCode, string $message = null): void
    {
        $this->responseToCore
            ->setExternalData(
                new SyntheticExternalData(
                    $responseCode,
                    $message
                )
            );
    }

    /**
     * @param string $responseType
     *
     * @return void
     */
    protected function handleMalformedError(string $responseType = ServiceResponse::TYPE_DECLINE): void
    {
        $this->responseToCore
            ->setType($responseType)
            ->setCode(Code::MALFORMED_RESPONSE)
            ->setExternalData(
                new SyntheticExternalData(
                    Code::MALFORMED_RESPONSE,
                    $this->responseExceptionMessage
                )
            );
    }

    /**
     * @param string $responseExceptionMessage
     *
     * @return $this
     */
    public function setResponseExceptionMessage(string $responseExceptionMessage): self
    {
        $this->responseExceptionMessage = $responseExceptionMessage;

        return $this;
    }

    /**
     * @param AbstractParameters $psParams
     *
     * @return AbstractPayoutCoreHandler
     */
    public function setPsParams(AbstractParameters $psParams): self
    {
        $this->psParams = $psParams;

        return $this;
    }
}
