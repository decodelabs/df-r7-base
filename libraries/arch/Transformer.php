<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch;

use df;
use df\core;
use df\arch;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;

abstract class Transformer implements ITransformer
{
    use core\TContextProxy;

    public static function loadNode(IContext $context)
    {
        $transformer = self::factory($context);

        if ($transformer) {
            $output = $transformer->execute();

            if ($output && !$output instanceof arch\node\INode) {
                throw Exceptional::{'df/arch/InvalidNode,UnexpectedValue'}(
                    'Transformer '.get_class($transformer).' returned an invalid node', [
                        'data' => $output
                    ]
                );
            }

            return $output;
        } else {
            return null;
        }
    }

    public static function isNodeDeliverable(IContext $context): bool
    {
        $transformer = self::factory($context);

        if (!$transformer) {
            return false;
        }

        return $transformer->canDeliver();
    }

    public static function factory(IContext $context): ?ITransformer
    {
        $runMode = Genesis::$kernel->getMode();
        $class = self::getClassFor($context->location, $runMode);

        if (!$class) {
            return null;
        }

        return new $class($context);
    }

    public static function getClassFor(IRequest $request, string $runMode='Http')
    {
        $runMode = ucfirst($runMode);
        $mainParts = $sharedParts = explode('/', $request->getDirectoryLocation());
        $class = null;

        while (!empty($mainParts)) {
            $class = 'df\\apex\\directory\\'.implode('\\', $mainParts).'\\'.$runMode.'Transformer';

            if (class_exists($class)) {
                break;
            } else {
                $class = null;
            }

            array_pop($mainParts);
        }

        if (!$class) {
            $sharedParts[0] = 'shared';

            while (!empty($sharedParts)) {
                $class = 'df\\apex\\directory\\'.implode('\\', $sharedParts).'\\'.$runMode.'Transformer';

                if (class_exists($class)) {
                    break;
                } else {
                    $class = null;
                }

                array_pop($sharedParts);
            }
        }

        return $class;
    }

    protected function __construct(arch\IContext $context)
    {
        $this->context = $context;
    }

    public function canDeliver()
    {
        return false;
    }

    public function getSitemapEntries(): iterable
    {
        return [];
    }
}
