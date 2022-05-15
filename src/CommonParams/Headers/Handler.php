<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\Headers;

use Exception;
use Plus\LocalProxy;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractHandler;

class Handler extends AbstractHandler
{
    private const
        AUTHORIZATION_HEADER_VALUE_PREFIX = 'Bearer ',
        REQUEST_ID_HEADER_LENGTH = 32;

    /** @var string */
    private $apiKey;

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function handle(): self
    {
        $this->setParameters(
            new Parameters(
                [
                    Parameters::AUTHORIZATION_HEADER_KEY =>
                        self::AUTHORIZATION_HEADER_VALUE_PREFIX . $this->apiKey,
                    Parameters::REQUEST_ID_HEADER_KEY    => self::generateSalt()
                ]
            )
        );

        return $this;
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    private static function generateSalt(): string
    {
        return LocalProxy::getSequenceGenerator()->generateRandomString(self::REQUEST_ID_HEADER_LENGTH);
    }
}
