<?php

namespace App\Services;

class AuthRules
{
    const NAME_MIN_LENGTH = 2;
    const NAME_MAX_LENGTH = 20;

    const PASSWORD_MIN_LENGTH = 6;
    const PASSWORD_MAX_LENGTH = 16;

    const PASSWORD_ALLOWED_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-+*=/.,{}<>?;';
    const PASSWORD_VALIDATION_REGEXP = '/^[a-zA-Z0-9!@#$%^&*()\-+*=\/.,{}<>?]+$/';

    /**
     * @return string
     */
    public static function getNameValidationRule()
    {
        return sprintf('required|min:%s|max:%s|regex:/(^([a-zA-Z ]+)$)/u',
            self::NAME_MIN_LENGTH,
            self::NAME_MAX_LENGTH
        );
    }

    /**
     * @return string
     */
    public static function getPasswordValidationRule()
    {
        return sprintf(
            'required|min:%s|max:%s|regex:%s',
            self::PASSWORD_MIN_LENGTH,
            self::PASSWORD_MAX_LENGTH,
            self::PASSWORD_VALIDATION_REGEXP
        );
    }

    /**
     * @return string
     */
    public static function getEmailValidationRule()
    {
        return 'required|email';
    }

    /**
     * @return string
     */
    public static function getActivationCodeValidationRule()
    {
        return self::getPasswordValidationRule();
    }

    /**
     * @return string
     */
    public static function getResetCodeValidationRule()
    {
        return self::getPasswordValidationRule();
    }
}