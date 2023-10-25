<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Genesis;

trait EnvNameTrait
{
    use ConfigTrait {
        getRepositoryName as getRepositoryNameParent;
    }

    public static function getRepositoryName(): string
    {
        $output = static::getRepositoryNameParent();
        $name = Genesis::$environment->getName();
        return $output . '#' . $name;
    }
}
