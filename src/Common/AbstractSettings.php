<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

use Exception;
use Plus\Common\Interfaces\Response\Code;
use Plus\PaymentSystem\Interfaces\GateSettings as IGateSettings;
use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\Interfaces\Response as IResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\Headers\Handler as HeadersHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\MerchantParams\Handler as MerchantParamsHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\MerchantParams\Parameters as MerchantParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\Headers\Parameters as Headers;
use Plus\PaymentSystem\Response;

abstract class AbstractSettings
{
    /** @var IGateSettings */
    private $gateSettings;

    /** @var IRequest */
    private $coreRequest;

    /** @var IResponse */
    private $responseToCore;

    /** @var MerchantParams */
    private $merchantParams;

    /** @var Headers */
    private $headers;

    /** @var bool */
    private $isFinRisk;

    /**
     * @param IRequest      $coreRequest
     * @param IGateSettings $gateSettings
     * @param bool          $isFinRisk
     *
     * @throws Exception
     */
    public function __construct(
        IRequest $coreRequest,
        IGateSettings $gateSettings,
        bool $isFinRisk = false
    ) {
        $this->coreRequest = $coreRequest;
        $this->responseToCore = $this->createBaseResponse($coreRequest);
        $this->gateSettings = $gateSettings;
        $merchantParamsHandler = new MerchantParamsHandler($this->coreRequest->getMerchantParams());
        $merchantParamsHandler->handle();
        $this->merchantParams = $merchantParamsHandler->getParameters();
        $this->headers = (new HeadersHandler($this->merchantParams->getApiKey()))->handle()->getParameters();
        $this->isFinRisk = $isFinRisk;
    }

    /**
     * @return IGateSettings
     */
    public function getGateSettings(): IGateSettings
    {
        return $this->gateSettings;
    }

    /**
     * @return IRequest
     */
    public function getCoreRequest(): IRequest
    {
        return $this->coreRequest;
    }

    /**
     * @return IResponse
     */
    public function getResponseToCore(): IResponse
    {
        return $this->responseToCore;
    }

    /**
     * @return MerchantParams
     */
    public function getMerchantParams(): MerchantParams
    {
        return $this->merchantParams;
    }

    /**
     * @return Headers
     */
    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    /**
     * @return bool
     */
    public function isFinRisk(): bool
    {
        return $this->isFinRisk;
    }

    /**
     * @param IRequest $request
     *
     * @return IResponse
     */
    protected function createBaseResponse(IRequest $request): IResponse
    {
        return (new Response())
            ->setCode(Code::DEFAULT)
            ->setOperationId($request->getOperationId())
            ->setRequestType($request->getType());
    }

    /**
     * @param bool $isFinRisk
     *
     * @return void
     */
    public function setIsFinRisk(bool $isFinRisk): void {
        $this->isFinRisk = $isFinRisk;
    }
}
