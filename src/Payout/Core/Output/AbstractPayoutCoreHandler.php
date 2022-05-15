<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Output;

use Exception;
use Plus\Common\Interfaces\Response\Code;
use Plus\Common\Interfaces\ServiceResponse;
use Plus\PaymentSystem\Interfaces\Response as IResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractFlow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractInputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Flow;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\PsParams;

abstract class AbstractPayoutCoreHandler extends AbstractCoreHandler
{
    //parameter names
    public const
        CHECK_STATUS_SERVICE_NAME = 'service_name',
        CHECK_STATUS_OPERATION_TYPE = 'operation_type',
        CHECK_STATUS_DATA_EXTERNAL_ID = 'external_data_id';

    public const
        CHECK_STATUS_DATA_EXTERNAL_ID_NAME = 'id';

    /** @var array */
    protected $defaultCheckStatusData;

    /** @var PsParams */
    protected $psParams;

    /** @var Flow */
    protected $flow;

    /** @var int */
    protected $responseStatusCode;

    /**
     * @param IResponse    $response
     * @param AbstractFlow $flow
     * @param array        $defaultCheckStatusData
     */
    public function __construct(IResponse $response, AbstractFlow $flow, array $defaultCheckStatusData)
    {
        parent::__construct($response, $flow);
        $this->defaultCheckStatusData = $defaultCheckStatusData;
        $this->buildScenarios();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $scenario = $this->flow->defineScenario();

        $handleFunction = $scenario->getHandleFunction();
        if (is_callable($handleFunction)) {
            $handleFunction();
        }

        $transitionStageName = $scenario->getTransitionStageName();

        if ($transitionStageName !== $this->flow->getCurrentStage()->getName()) {
            $this->flow->setCurrentStage($transitionStageName);
            $this->flow->move();
        }
    }

    /**
     * @param int $responseStatusCode
     *
     * @return $this
     */
    public function setResponseStatusCode(int $responseStatusCode): self
    {
        $this->responseStatusCode = $responseStatusCode;

        return $this;
    }

    /**
     * @return void
     */
    abstract protected function buildScenarios(): void;

    /**
     * @param string $responseType
     *
     * @return void
     */
    protected function handleNetworkError(string $responseType = ServiceResponse::TYPE_DECLINE): void
    {
        $this->responseToCore
            ->setCode(Code::NETWORK_ERROR)
            ->setType($responseType);
        $this->handleSyntheticData(
            Code::NETWORK_ERROR,
            $this->responseExceptionMessage
        );
    }

    /**
     * @param string $id
     *
     * @return void
     */
    protected function handleCheckStatusData(string $id): void
    {
        $this->responseToCore->getCheckStatusData()
            ->setServiceName($this->defaultCheckStatusData[self::CHECK_STATUS_SERVICE_NAME])
            ->setOperationType($this->defaultCheckStatusData[self::CHECK_STATUS_OPERATION_TYPE])
            ->setExternalData(
                [
                    self::CHECK_STATUS_DATA_EXTERNAL_ID_NAME => $id,
                ]
            );
    }

    /**
     * @return void
     */
    protected function handleBadResponseError(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_DECLINE)
            ->setCode(Code::DEFAULT)
            ->getExternalData()
            ->setMessage(
                $this->psParams->getErrorDescription() ?? AbstractInputHandler::DESCRIPTION_DEFAULT_VALUE
            )
            ->setCode($this->psParams->getErrorType());
    }

    /**
     * @return void
     */
    protected function handleWaiting(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_WAITING)
            ->setCode(Code::DEFAULT);
    }

    /**
     * @return void
     */
    protected function handleError409or422(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_DECLINE)
            ->setCode(Code::DEFAULT);

        $externalDataCode = $this->psParams->getErrorMessage();
        if ($externalDataCode) {
            $this->responseToCore
                ->getExternalData()
                ->setCode($externalDataCode)
                ->setMessage(AbstractInputHandler::DESCRIPTION_DEFAULT_VALUE);
        } else {
            $this->handleSyntheticData(
                Code::NETWORK_ERROR,
                (string)$this->responseStatusCode
            );
        }
    }

    /**
     * @return void
     */
    protected function handleInvalidAmountAndCurrency(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_SUCCESS)
            ->setCode(Code::INVALID_AMOUNT_OR_CURRENCY)
            ->getExternalData()
            ->setId($this->psParams->getWithdrawalId())
            ->setAmount($this->psParams->getBodyAmount())
            ->setCurrency($this->psParams->getBodyCurrency())
            ->setCode($this->psParams->getStatus());
    }

    /**
     * @return void
     */
    protected function handleSuccess(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_SUCCESS)
            ->setCode(Code::DEFAULT)
            ->getExternalData()
            ->setCode($this->psParams->getStatus())
            ->setId($this->psParams->getWithdrawalId());
    }

    /**
     * @return void
     */
    protected function handleFailed(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_DECLINE)
            ->setCode(Code::DEFAULT)
            ->getExternalData()
            ->setId($this->psParams->getWithdrawalId())
            ->setCode($this->psParams->getStatus())
            ->setMessage($this->psParams->getFailureCode());
    }

    /**
     * @return void
     */
    protected function handleError401or404(): void
    {
        $this->responseToCore
            ->setType(ServiceResponse::TYPE_DECLINE)
            ->setCode(Code::DEFAULT);
        $this->handleSyntheticData(
            Code::DEFAULT,
            (string)$this->responseStatusCode
        );
    }

    /**
     * @param string $responseType
     *
     * @return void
     */
    protected function handleInvalidResponseCode(string $responseType = ServiceResponse::TYPE_DECLINE): void
    {
        $this->setResponseExceptionMessage(
            AbstractInputHandler::INVALID_HTTP_RESPONSE_STATUS_CODE_ERROR . $this->responseStatusCode
        );
        $this->handleNetworkError($responseType);
    }
}
