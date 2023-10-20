<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;

class Template implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        return [
        ];
    }
}
