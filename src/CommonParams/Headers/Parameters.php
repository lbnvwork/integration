<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\CommonParams\Headers;

use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractParameters;

class Parameters extends AbstractParameters
{
    public const
        AUTHORIZATION_HEADER_KEY = 'Authorization',
        REQUEST_ID_HEADER_KEY = 'X-Request-ID';

    /**
     * @param string $urlTemplate
     * @param string $value
     *
     * @return string
     */
    public function getUrlByTemplate(string $urlTemplate, string $value): string
    {
        return rtrim(
            preg_replace(
                '/\/{.+}\/?/',
                '/' . $value . '/',
                $urlTemplate
            ),
            '/'
        );
    }

    /**
     * @return string[]
     */
    public function getMaskedFields(): array
    {
        return [
            self::AUTHORIZATION_HEADER_KEY,
        ];
    }
}
