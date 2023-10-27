<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Genesis;

trait EnvNameTrait
{
    use ConfigTrait {
        getRepositoryName as getRepositoryNameParent;
    }

    protected static ?string $envId = null;
    protected static ?string $appPath = null;
    protected static ?string $name = null;

    public static function setEnvId(string $id): void
    {
        static::$envId = $id;
    }

    public static function setAppPath(string $path): void
    {
        static::$appPath = $path;
    }

    public static function getRepositoryName(): string
    {
        if(static::$name !== null) {
            return static::$name;
        }

        $output = static::getRepositoryNameParent();
        $name = static::$envId ?? Genesis::$environment->getName();
        $appPath = static::$appPath ?? Genesis::$hub->getApplicationPath();

        if(file_exists($appPath.'/config/'.$output.'#'.$name.'.php')) {
            return static::$name = $output. '#' . $name;
        }

        return static::$name = $output;
    }
}
