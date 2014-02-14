<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy;

use df;
use df\core;
    
abstract class Hook implements IHook {  

    use core\TContextProxy;

    protected static $_actionMap = [];

    public static function getClassList() {
        $list = [];

        foreach(df\Launchpad::$loader->lookupFileList('apex/hooks/', 'php') as $fileName => $path) {
            $name = substr($fileName, 0, -4);

            if(in_array($name, ['_manifest', 'Base'])) {
                continue;
            }

            $list[$name] = 'df\\apex\\hooks\\'.$name;
        }

        return $list;
    }

    public static function triggerEvent(IEvent $event, core\IContext $context=null) {
        $entityLocator = $event->getEntityLocator();

        if(!$entityLocator) {
            return;
        }

        if($context === null) {
            $context = new core\SharedContext(df\Launchpad::$application);
        }

        $cache = HookCache::getInstance($context->application);
        $domain = $entityLocator->getDomain();

        if(!$entitySet = $cache->get($domain)) {
            $emptySet = $cache->get('__empty', []);

            if(in_array($domain, $emptySet)) {
                return;
            }

            $actionMap = self::_generateActionMap($context);
            
            if(!isset($actionMap[$domain])) {
                $emptySet[] = $domain;
                $cache->set('__empty', $emptySet);
                return;
            }

            $entitySet = $actionMap[$domain];
        }
        
        $action = $event->getAction();

        if(!isset($entitySet[$action])) {
            return;
        }

        foreach($entitySet[$action] as $target) {
            list($hookName, $methodName) = explode(':', $target);
            $hook = self::factory($hookName, $context);
            $method = 'on'.ucfirst($methodName);

            if(method_exists($hook, $method)) {
                $hook->{$method}($event);
            }
        }
    }

    protected static function _generateActionMap(core\IContext $context) {
        $classList = self::getClassList();
        $map = [];

        foreach($classList as $name => $class) {
            if(!class_exists($class)) {
                continue;
            }

            $hook = new $class($context);
            
            foreach($hook->getActionMap() as $entityLocator => $entitySet) {
                $entityLocator = core\policy\entity\Locator::factory($entityLocator);
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
            $context = new core\SharedContext(df\Launchpad::$application);
        }

        return new $class($context);
    }

    public function __construct(core\IContext $context) {
        $this->_context = $context;
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getActionMap() {
        return static::$_actionMap;
    }
}