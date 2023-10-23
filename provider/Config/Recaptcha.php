<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Coercion;
use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;

class Recaptcha implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        return [
            'enabled' => false,
            'siteKey' => null,
            'secret' => null
        ];
    }

    public function isEnabled(): bool
    {
        return
            (bool)$this->data['enabled'] &&
            !empty($this->data['siteKey']) &&
            !empty($this->data['secret']);
    }

    public function getSiteKey(): ?string
    {
        return Coercion::toStringOrNull($this->data['siteKey']);
    }

    public function getSecret(): ?string
    {
        return Coercion::toStringOrNull($this->data['secret']);
    }
}
