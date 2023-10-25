<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;

class Users implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        return [
            'registrationEnabled' => false,
            'verifyEmail' => false,
            'loginOnRegistration' => true,
            'registrationLandingPage' => '/',
            'checkPasswordStrength' => true,
            'minPasswordStrength' => 18
        ];
    }

    public function isRegistrationEnabled(): bool
    {
        return $this->data->registrationEnabled->as('bool');
    }

    public function shouldVerifyEmail(): bool
    {
        return $this->data->verifyEmail->as('bool');
    }

    public function shouldLoginOnRegistration(): bool
    {
        return $this->data->loginOnRegistration->as('bool');
    }

    public function getRegistrationLandingPage(): string
    {
        return $this->data->registrationLandingPage->as('string', [
            'default' => '/account/'
        ]);
    }


    public function shouldCheckPasswordStrength(): bool
    {
        return $this->data->checkPasswordStrength->as('bool', [
            'default' => true
        ]);
    }

    public function getMinPasswordStrength(): int
    {
        return $this->data->minPasswordStrength->as(
            'int',
            [
            'default' => 18]
        );
    }
}
