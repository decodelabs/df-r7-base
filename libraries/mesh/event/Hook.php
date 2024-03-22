<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mesh\event;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Stash;
use df\core;
use df\mesh;

abstract class Hook implements IHook
{
    use core\TContextProxy;

    public const EVENTS = [];

    protected static $_enabled = true;

    public static function getClassList()
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('apex/hooks/', true) as $key => $val) {
            $output[$key] = $val;
        }

        return $output;
    }

    public static function toggleEnabled(bool $flag = null)
    {
        if ($flag === null) {
            $flag = !self::$_enabled;
        }

        self::$_enabled = $flag;
    }

    public static function triggerEvent(IEvent $event, core\IContext $context = null)
    {
        if (!self::$_enabled) {
            return;
        }

        $entityLocator = $event->getEntityLocator();

        if ($context === null) {
            $context = new core\SharedContext();
        }

        $domain = $entityLocator->getDomain();
        $eventMap = self::_generateEventMap($context);

        if (!isset($eventMap[$domain])) {
            return;
        }

        $entitySet = $eventMap[$domain];
        $action = $event->getAction();

        if (!isset($entitySet[$action])) {
            return;
        }

        $isProduction = Genesis::$environment->isProduction();
        $entity = $event->getCachedEntity();

        foreach ($entitySet[$action] as $target) {
            list($hookName, $methodName) = explode(':', $target);
            $hook = self::factory($hookName, $context);
            $method = 'on' . ucfirst($methodName);

            $ref = new \ReflectionClass($hook);

            if ($ref->hasMethod($method)) {
                if ($entity === null) {
                    $methodRef = $ref->getMethod($method);

                    if ($methodRef->getNumberOfParameters() > 1) {
                        $entity = $event->getEntity();
                    }
                }

                try {
                    $hook->{$method}($event, $entity);
                } catch (\Throwable $e) {
                    core\log\Manager::getInstance()->logException($e);

                    if (!$isProduction) {
                        throw $e;
                    }
                }
            }
        }
    }

    protected static function _generateEventMap(core\IContext $context)
    {
        $cache = Stash::load('mesh.hook');
        $isProduction = Genesis::$environment->isProduction();

        if (
            $isProduction &&
            $cache->has('eventMap')
        ) {
            return $cache->get('eventMap');
        }

        $classList = self::getClassList();
        $map = [];

        foreach ($classList as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            $hook = new $class($context);

            foreach ($hook->getEventMap() as $entityLocator => $entitySet) {
                $entityLocator = mesh\entity\Locator::factory($entityLocator);
                $domain = $entityLocator->getDomain();

                if (!isset($map[$domain])) {
                    $map[$domain] = [];
                }

                foreach ($entitySet as $key => $val) {
                    if (!is_array($val)) {
                        $val = [$val];
                    }

                    foreach ($val as $i => $methodName) {
                        $method = 'on' . ucfirst($methodName);

                        if (!method_exists($hook, $method)) {
                            throw Exceptional::UnexpectedValue(
                                'Action map method ' . $method . ' could not be found on hook ' . $name
                            );
                        }

                        $val[$i] = $name . ':' . $methodName;
                    }

                    if (!isset($map[$domain][$key])) {
                        $map[$domain][$key] = [];
                    }

                    $map[$domain][$key] = array_merge($map[$domain][$key], $val);
                }
            }
        }

        if ($isProduction) {
            $cache->set('eventMap', $map);
        }

        return $map;
    }

    public static function factory($name, core\IContext $context = null)
    {
        $class = 'df\\apex\\hooks\\' . ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::Runtime(
                'Hook ' . $name . ' could not be found'
            );
        }

        if ($context === null) {
            $context = new core\SharedContext();
        }

        return new $class($context);
    }

    public function __construct(core\IContext $context)
    {
        $this->context = $context;
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function getEventMap()
    {
        return (array)static::EVENTS;
    }
}
