<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;

abstract class Base implements IScaffold {
    
    //use core\TContextProxy;
    use core\TContextAware;
    use aura\view\TCascadingHelperProvider;
    use arch\TDirectoryAccessLock;
    use arch\TOptionalDirectoryAccessLock;

    const DIRECTORY_KEY_NAME = null;
    const DIRECTORY_TITLE = null;
    const DIRECTORY_ICON = null;

    const CHECK_ACCESS = true;
    const DEFAULT_ACCESS = null;

    private $_directoryKeyName;

    protected $_propagateQueryVars = [];

    public static function factory(arch\IContext $context) {
        $registryKey = 'scaffold('.$context->location->getPath()->getDirname().')';

        if($output = $context->application->getRegistryObject($registryKey)) {
            return $output;
        }

        $runMode = $context->getRunMode();
        $class = self::getClassFor($context->location, $runMode);
        
        if(!$class) {
            throw new RuntimeException('Scaffold could not be found for '.$context->location);
        }
        
        $output = new $class($context);
        $context->application->setRegistryObject($output);

        return $output;
    }

    public static function getClassFor(arch\IRequest $request, $runMode='Http') {
        $runMode = ucfirst($runMode);
        $parts = $request->getControllerParts();
        $parts[] = $runMode.'Scaffold';
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);

        if(!class_exists($class)) {
            $class = null;
        }

        return $class;
    }
    
    protected function __construct(arch\IContext $context) {
        $this->_context = $context;
        $this->view = aura\view\Base::factory($context->request->getType(), $this->_context);
    }

    public function getRegistryObjectKey() {
        return 'scaffold('.$this->_context->location->getPath()->getDirname().')';
    }

    public function onApplicationShutdown() {}

    public function getView() {
        return $this->view;
    }

    public function getPropagatingQueryVars() {
        return (array)$this->_propagateQueryVars;
    }

    protected function _buildQueryPropagationInputs(array $filter=[]) {
        $output = [];

        foreach($this->getPropagatingQueryVars() as $var) {
            if(in_array($var, $filter)) {
                continue;
            }

            $output[] = $this->html->hidden($var, $this->request->query[$var]);
        }

        return $output;
    }


// Loaders
    public function loadAction(arch\IController $controller=null) {
        $action = $this->_context->request->getAction();
        $method = lcfirst($action).$this->_context->request->getType().'Action';
        
        if(!method_exists($this, $method)) {
            $method = lcfirst($action).'Action';

            if(!method_exists($this, $method)) {
                $method = 'build'.ucfirst($action).'DynamicAction';

                if(method_exists($this, $method)) {
                    $action = $this->{$method}($controller);

                    if($action instanceof arch\IAction) {
                        return $action;
                    }
                }

                if($this instanceof ISectionProviderScaffold && ($action = $this->loadSectionAction($controller))) {
                    return $action;
                }

                throw new ActionNotFoundException(
                    'Scaffold at '.$this->_context->location.' cannot provide action '.$action
                );
            }
        }

        return new Action($this->_context, $this, [$this, $method], $controller);
    }

    public function onActionDispatch(arch\IAction $action) {}

    public function loadComponent($name, array $args=null) {
        $keyName = $this->_getDirectoryKeyName();
        $origName = $name;

        if(substr($name, 0, strlen($keyName)) == ucfirst($keyName)) {
            $name = substr($name, strlen($keyName));
        }

        $method = 'generate'.$name.'Component';

        if(method_exists($this, $method)) {
            return new arch\scaffold\component\Generic($this, $name, $args);
        }
        

        $method = 'build'.$name.'Component';

        if(method_exists($this, $method)) {
            $output = $this->{$method}($args);

            if(!$output instanceof arch\IComponent) {
                throw new LogicException(
                    'Scaffold at '.$this->_context->location.' attempted but failed to provide component '.$origName
                );
            }

            return $output;
        }

        throw new LogicException(
            'Scaffold at '.$this->_context->location.' cannot provide component '.$origName
        );
    }

    public function loadFormDelegate($name, arch\form\IStateController $state, $id) {
        $keyName = $this->_getDirectoryKeyName();
        $origName = $name;

        if(substr($name, 0, strlen($keyName)) == ucfirst($keyName)) {
            $name = substr($name, strlen($keyName));
        }

        $method = 'build'.$name.'FormDelegate';
        
        if(!method_exists($this, $method)) {
            throw new LogicException(
                'Scaffold at '.$this->_context->location.' cannot provide form delegate '.$origName
            );
        }
        
        $output = $this->{$method}($state, $id);

        if(!$output instanceof arch\form\IDelegate) {
            throw new LogicException(
                'Scaffold at '.$this->_context->location.' attempted but failed to provide form delegate '.$origName
            );
        }

        return $output;
    }

    public function loadMenu($name, $id) {
        $method = 'generate'.ucfirst($name).'Menu';

        if(!method_exists($this, $method)) {
            throw new LogicException(
                'Scaffold at '.$this->_context->location.' could not provider menu '.$name
            );
        }

        return new arch\scaffold\navigation\Menu($this, $name, $id);
    }


// Directory
    public function getDirectoryTitle() {
        if(static::DIRECTORY_TITLE) {
            return $this->_(static::DIRECTORY_TITLE);
        }

        return $this->format->name($this->_getDirectoryKeyName());
    }

    public function getDirectoryIcon() {
        if(static::DIRECTORY_ICON) {
            return static::DIRECTORY_ICON;
        }

        return $this->_getDirectoryKeyName();
    }

// Helpers
    protected function _getDirectoryKeyName() {
        if($this->_directoryKeyName) {
            return $this->_directoryKeyName;
        }

        if(static::DIRECTORY_KEY_NAME) {
            $this->_directoryKeyName = static::DIRECTORY_KEY_NAME;
        } else {
            $parts = $this->_context->location->getControllerParts();
            $this->_directoryKeyName = array_pop($parts);
        }

        return $this->_directoryKeyName;
    }

    protected function _getActionRequest($action, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]) {
        $output = clone $this->_context->location;
        $output->setAction($action);
        $outQuery = $output->query;
        $propagate = $this->getPropagatingQueryVars();

        foreach($outQuery->getKeys() as $key) {
            if(!in_array($key, $propagate)) {
                unset($outQuery->{$key});
            }
        }

        if($query !== null) {
            $outQuery->import($query);
        }

        foreach($propagate as $var) {
            if(!in_array($var, $propagationFilter)) {
                $outQuery->{$var} = $this->request->query[$var];
            }
        }

        foreach($propagationFilter as $var) {
            unset($outQuery->{$var});
        }

        return $this->directory->normalizeRequest($output, $redirFrom, $redirTo);
    }

    protected function _normalizeFieldOutput($field, $value) {
        if($value instanceof core\time\IDate) {
            return $this->format->userDateTime($value, 'short');
        }

        return $value;
    }
}