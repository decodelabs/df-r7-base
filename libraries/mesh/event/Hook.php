<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\event;

use df;
use df\core;
use df\mesh;
use df\axis;

abstract class Hook implements IHook {

    use core\TContextProxy;

    const EVENTS = [];

    protected static $_enabled = true;

    public static function getClassList() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupClassList('apex/hooks/', true) as $key => $val) {
            $output[$key] = $val;
        }

        return $output;
    }

    public static function toggleEnabled(bool $flag=null) {
        if($flag === null) {
            $flag = !self::$_enabled;
        }

        self::$_enabled = $flag;
    }

    public static function triggerEvent(IEvent $event, core\IContext $context=null) {
        if(!self::$_enabled) {
            return;
        }

        $entityLocator = $event->getEntityLocator();

        if($context === null) {
            $context = new core\SharedContext();
        }

        $cache = HookCache::getInstance();
        $domain = $entityLocator->getDomain();

        if(!$entitySet = $cache->get($domain)) {
            $emptySet = $cache->get('__empty', []);

            if(in_array($domain, $emptySet)) {
                return;
            }

            $eventMap = self::_generateEventMap($context);

            if(!isset($eventMap[$domain])) {
                $emptySet[] = $domain;
                $cache->set('__empty', $emptySet);
                return;
            }

            $entitySet = $eventMap[$domain];
        }

        $action = $event->getAction();

        if(!isset($entitySet[$action])) {
            return;
        }

        $isProduction = df\Launchpad::$application->isProduction();
        $entity = $event->getCachedEntity();

        foreach($entitySet[$action] as $target) {
            list($hookName, $methodName) = explode(':', $target);
            $hook = self::factory($hookName, $context);
            $method = 'on'.ucfirst($methodName);

            $ref = new \ReflectionClass($hook);

            if($ref->hasMethod($method)) {
                if($entity === null) {
                    $methodRef = $ref->getMethod($method);

                    if($methodRef->getNumberOfParameters() > 1) {
                        $entity = $event->getEntity();
                    }
                }

                try {
                    $hook->{$method}($event, $entity);
                } catch(\Throwable $e) {
                    core\log\Manager::getInstance()->logException($e);

                    if(!$isProduction) {
                        throw $e;
                    }
                }
            }
        }
    }

    protected static function _generateEventMap(core\IContext $context) {
        $classList = self::getClassList();
        $map = [];

        foreach($classList as $name => $class) {
            if(!class_exists($class)) {
                continue;
            }

            $hook = new $class($context);

            foreach($hook->getEventMap() as $entityLocator => $entitySet) {
                $entityLocator = mesh\entity\Locator::factory($entityLocator);
                $domain = $entityLocator->getDomain();

                if(!isset($map[$domain])) {
                    $map[$domain] = [];
                }

                foreach($entitySet as $key => $val) {
                    if(!is_array($val)) {
                        $val = [$val];
                    }

                    foreach($val as $i => $methodName) {
                        $method = 'on'.ucfirst($methodName);

                        if(!method_exists($hook, $method)) {
                            throw new UnexpectedValueException(
                                'Action map method '.$method.' could not be found on hook '.$name
                            );
                        }

                        $val[$i] = $name.':'.$methodName;
                    }

                    if(!isset($map[$domain][$key])) {
                        $map[$domain][$key] = [];
                    }

                    $map[$domain][$key] = array_merge($map[$domain][$key], $val);
                }
            }
        }

        return $map;
    }

    public static function factory($name, core\IContext $context=null) {
        $class = 'df\\apex\\hooks\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Hook '.$name.' could not be found'
            );
        }

        if($context === null) {
            $context = new core\SharedContext();
        }

        return new $class($context);
    }

    public function __construct(core\IContext $context) {
        $this->context = $context;
    }

    public function getName(): string {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getEventMap() {
        return (array)static::EVENTS;
    }
}
