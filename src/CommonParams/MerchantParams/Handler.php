<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\MerchantParams;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractInputHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\MerchantParams\Parameters as MerchantParams;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers\Assert;

/**
 * Class handles merchant account parameters
 */
class Handler extends AbstractInputHandler
{
    /**
     * @var array
     */
    private $merchantParamsData;

    /**
     * @param array $merchantParamsData
     */
    public function __construct(array $merchantParamsData)
    {
        parent::__construct();
        $this->merchantParamsData = $merchantParamsData;
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
        $this->setParameters(new MerchantParams($this->merchantParamsData));
        $this->validate();
    }

    /**
     * @return void
     */
    public function validate(): void
    {
        $merchantParams = $this->parameters->getAll();

        $merchantParams[MerchantParams::PAYOUT_CHECK_CREATE_URL] =
            $this->removeTemplateFromUrl($merchantParams[MerchantParams::PAYOUT_CHECK_CREATE_URL]);
        $merchantParams[MerchantParams::CHECK_STATUS_URL] =
            $this->removeTemplateFromUrl($merchantParams[MerchantParams::CHECK_STATUS_URL]);
        $merchantParams[MerchantParams::BALANCE_URL] =
            $this->removeTemplateFromUrl($merchantParams[MerchantParams::BALANCE_URL]);

        $this->validator->validateTypeRequired(
            $merchantParams,
            [
                MerchantParams::NAME                               => Assert::assertNotBlankString(),
                MerchantParams::IDENTITY                           => Assert::assertNotBlankString(),
                MerchantParams::WALLET_ID                          => Assert::assertNotBlankString(),
                MerchantParams::API_KEY                            => Assert::assertNotBlankString(),
                MerchantParams::PAYOUT_CREATE_URL                  => Assert::assertNotBlankUrl(),
                MerchantParams::PAYOUT_CHECK_CREATE_URL            => Assert::assertNotBlankUrl(),
                MerchantParams::PAYOUT_URL_COMPLETE                => Assert::assertNotBlankUrl(),
                MerchantParams::BALANCE_URL                        => Assert::assertNotBlankUrl(),
                MerchantParams::CHECK_STATUS_URL                   => Assert::assertNotBlankUrl(),
                MerchantParams::DESTINATIONS_CHECK_STATUS_LIFETIME => Assert::assertNotBlankInt(),
            ]
        );

        $this->validator->validateType(
            $merchantParams,
            [
                MerchantParams::MIN_PAYOUT_AMOUNT     => Assert::assertInteger(),
                MerchantParams::MAX_PAYOUT_AMOUNT     => Assert::assertInteger(),
                MerchantParams::CHECK_STATUS_LIFETIME => Assert::assertInteger(),
            ]
        );
    }

    /**
     * @param string $statusUrl
     *
     * @return string
     */
    protected function removeTemplateFromUrl(string $statusUrl): string
    {
        return preg_replace(
            '/\/{.+}\/?/',
            '/',
            $statusUrl
        );
    }
}
