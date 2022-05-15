<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Output;

use Plus\Common\Interfaces\Response\Code;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\PaymentSystem\Interfaces\ExternalData;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Input\Parameters;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;

class CoreHandler extends AbstractCoreHandler
{
    /** @var Parameters */
    protected $psParams;

    //scenarios
    public const
        SUCCESS = '4.0',
        NOT_EQUAL_AVAILABLE = '4.1',
        CLIENT_EXCEPTION = 'client_exception';

    /**
     * @return void
     */
    public function handle(): void
    {
        switch ($this->flow->getCurrentScenarioName()) {
            case self::SUCCESS:
                $this->responseToCore
                    ->setType(ServiceResponse::TYPE_SUCCESS)
                    ->setCode(Code::DEFAULT)
                    ->getExternalData()
                    ->setBalances(
                        [
                            [
                                ExternalData::FIELD_AMOUNT   => (int)$this->psParams->getAvailableAmount(),
                                ExternalData::FIELD_CURRENCY => $this->psParams->getAvailableCurrency(),
                            ]
                        ]
                    );
                break;
            case self::NOT_EQUAL_AVAILABLE:
                $this->responseToCore
                    ->setType(ServiceResponse::TYPE_DECLINE)
                    ->setCode(Code::DEFAULT)
                    ->getExternalData()
                    ->setCode(Parameters::DEFAULT_ERROR_VALUE)
                    ->setMessage($this->responseExceptionMessage);
                break;
            case self::CLIENT_EXCEPTION:
                $this->responseToCore
                    ->setType(ServiceResponse::TYPE_DECLINE)
                    ->setCode(Code::DEFAULT)
                    ->getExternalData()
                    ->setCode($this->psParams->getErrorType())
                    ->setMessage($this->psParams->getErrorDescription());
                break;
            case self::MALFORMED:
                $this->responseToCore
                    ->setType(ServiceResponse::TYPE_DECLINE)
                    ->setCode(Code::MALFORMED_RESPONSE);
                $this->handleSyntheticData(
                    Code::MALFORMED_RESPONSE,
                    $this->responseExceptionMessage
                );
                break;
            case self::NETWORK_ERROR:
                $this->responseToCore
                    ->setType(ServiceResponse::TYPE_DECLINE)
                    ->setCode(Code::NETWORK_ERROR);
                $this->handleSyntheticData(
                    Code::NETWORK_ERROR,
                    $this->responseExceptionMessage
                );
                break;
            default:
                //do nothing
        }
    }
}
