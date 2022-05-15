<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Core\Input;

use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractInputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;

/**
 * Handles parameters of core-plus request
 */
class ClientParamsHandler extends AbstractInputHandler
{
    /**
     * @param array $inputData
     */
    public function __construct(array $inputData)
    {
        parent::__construct();
        $this->inputData = $inputData;
    }

    /**
     * @return void
     */
    public function validate(): void
    {
        $this->validateClientParams();
    }

    /**
     * @return void
     */
    private function validateClientParams(): void
    {
        $clientParams = $this->getParameters();
        $this->validator->validateTypeRequired(
            $clientParams->getAll(),
            [
                IRequest::CP_ACCOUNT => Assert::assertNotBlankString(),
                IRequest::CP_EMAIL   => Assert::assertEmail()
            ]
        );

        $this->validator->validateRequired(
            $clientParams->getAll(),
            [
                IRequest::CP_CUSTOM,
            ]
        );

        $this->validator->validateTypeRequired(
            $clientParams->get(IRequest::CP_CUSTOM),
            [
                ClientParams::ACCOUNTHOLDER => Assert::assertNotBlankString(),
            ]
        );
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->setParameters(new ClientParams($this->inputData));
        $this->validate();
    }
}
