<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\menu\source;

use DecodeLabs\Exceptional;

use df\arch;
use df\core;

abstract class Base implements arch\navigation\menu\ISource, core\IContextAware
{
    use core\TContextAware;

    public static function factory(arch\IContext $context, $type): arch\navigation\menu\ISource
    {
        $class = 'df\\arch\\navigation\\menu\\source\\' . ucfirst($type);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Source type ' . $type . ' could not be found'
            );
        }

        return new $class($context);
    }

    public function __construct(arch\IContext $context)
    {
        $this->context = $context;
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function getDisplayName(): string
    {
        return $this->getName();
    }
}
