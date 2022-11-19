<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;

use df\arch\IContext as Context;
use df\arch\IRequest as Request;
use df\arch\Scaffold;

class Loader
{
    public static function fromContext(Context $context): Scaffold
    {
        $registryKey = 'scaffold(' . $context->location->getPath()->getDirname() . ')';

        if ($output = Legacy::getRegistryObject($registryKey)) {
            /** @var Scaffold $output */
            return $output;
        }

        $class = self::getClassFromRequest(
            $context->location,
            Genesis::$kernel->getMode()
        );

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Scaffold could not be found for ' . $context->location
            );
        }

        $output = new $class($context);
        Legacy::setRegistryObject($output);

        return $output;
    }

    public static function getClassFromRequest(Request $request, string $runMode = 'Http'): string
    {
        $runMode = ucfirst($runMode);
        $parts = $request->getControllerParts();
        $parts[] = $runMode . 'Scaffold';

        return 'df\\apex\\directory\\' . $request->getArea() . '\\' . implode('\\', $parts);
    }
}
