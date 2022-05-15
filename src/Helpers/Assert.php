<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Helpers;

use Symfony\Component\Validator\Constraints as BaseAssert;

class Assert
{
    /**
     * @return BaseAssert\Type
     */
    public static function assertInteger(): BaseAssert\Type
    {
        return new BaseAssert\Type('integer');
    }

    /**
     * @return BaseAssert\Type
     */
    public static function assertString(): BaseAssert\Type
    {
        return new BaseAssert\Type('string');
    }

    /**
     * @return BaseAssert\Email
     */
    public static function assertEmail(): BaseAssert\Email
    {
        return new BaseAssert\Email();
    }

    /**
     *
     * @return BaseAssert\Url
     */
    public static function assertUrl(): BaseAssert\Url
    {
        return new BaseAssert\Url();
    }

    public static function assertCurrency(): BaseAssert\Currency
    {
        return new BaseAssert\Currency();
    }

    /**
     * @return BaseAssert\NotBlank
     */
    public static function assertNotBlank(): BaseAssert\NotBlank
    {
        return new BaseAssert\NotBlank();
    }

    /**
     * @return BaseAssert\Type
     */
    public static function assertArray(): BaseAssert\Type
    {
        return new BaseAssert\Type('array');
    }

    /**
     * @param             $value
     * @param string|null $message
     *
     * @return BaseAssert\EqualTo
     */
    public static function assertEqualTo($value, ?string $message = null): BaseAssert\EqualTo
    {
        return new BaseAssert\EqualTo($value, null, $message);
    }

    /**
     * @param array $choices
     *
     * @return BaseAssert\Choice
     */
    public static function assertChoice(array $choices): BaseAssert\Choice
    {
        return new BaseAssert\Choice(
            [
                'choices' => $choices
            ]
        );
    }

    /** @return array */
    public static function assertNotBlankString(): array
    {
        return [
            self::assertNotBlank(),
            self::assertString(),
        ];
    }

    /** @return array */
    public static function assertNotBlankUrl(): array
    {
        return [
            self::assertNotBlank(),
            self::assertUrl(),
        ];
    }

    /** @return array */
    public static function assertNotBlankArray(): array
    {
        return [
            self::assertNotBlank(),
            self::assertArray(),
        ];
    }

    /**
     * @return array
     */
    public static function assertNotBlankCurrency(): array
    {
        return [
            self::assertNotBlank(),
            self::assertCurrency(),
        ];
    }

    /**
     * @return array
     */
    public static function assertNotBlankInt(): array
    {
        return [
            self::assertNotBlank(),
            self::assertInteger(),
        ];
    }
}
