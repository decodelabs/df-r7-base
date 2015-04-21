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
        $this->context = $context;
        $this->view = aura\view\Base::factory($context->request->getType(), $this->context);
    }

    public function getRegistryObjectKey() {
        return 'scaffold('.$this->context->location->getPath()->getDirname().')';
    }

    public function getView() {
        return $this->view;
    }

    public function getPropagatingQueryVars() {
        return (array)$this->_propagateQueryVars;
    }

    protected function _buildQueryPropagationInputs(array $filter=[]) {
        $output = [];
        $vars = array_merge(
            $this->getPropagatingQueryVars(),
            $this->request->query->getKeys()
        );


        foreach($vars as $var) {
            if(in_array($var, $filter)) {
                continue;
            }

            $output[] = $this->html->hidden($var, $this->request->query[$var]);
        }

        return $output;
    }


// Loaders
    public function loadAction() {
        $action = $this->context->request->getAction();
        $method = lcfirst($action).$this->context->request->getType().'Action';
        
        if(!method_exists($this, $method)) {
            $method = lcfirst($action).'Action';

            if(!method_exists($this, $method)) {
                $method = 'build'.ucfirst($action).'DynamicAction';

                if(method_exists($this, $method)) {
                    $action = $this->{$method}();

                    if($action instanceof arch\IAction) {
                        return $action;
                    }
                }

                if($this instanceof ISectionProviderScaffold && ($action = $this->loadSectionAction())) {
                    return $action;
                }

                throw new ActionNotFoundException(
                    'Scaffold at '.$this->context->location.' cannot provide action '.$action
                );
            }
        }

        return $this->_generateAction([$this, $method]);
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
                    'Scaffold at '.$this->context->location.' attempted but failed to provide component '.$origName
                );
            }

            return $output;
        }

        throw new LogicException(
            'Scaffold at '.$this->context->location.' cannot provide component '.$origName
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
                'Scaffold at '.$this->context->location.' cannot provide form delegate '.$origName
            );
        }
        
        $output = $this->{$method}($state, $id);

        if(!$output instanceof arch\form\IDelegate) {
            throw new LogicException(
                'Scaffold at '.$this->context->location.' attempted but failed to provide form delegate '.$origName
            );
        }

        return $output;
    }

    public function loadMenu($name, $id) {
        $method = 'generate'.ucfirst($name).'Menu';

        if(!method_exists($this, $method)) {
            throw new LogicException(
                'Scaffold at '.$this->context->location.' could not provider menu '.$name
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


// Generators
    public function generateAttributeList(array $fields, $record=true) {
        if($record === true) {
            $record = $this->getRecord();
        }

        $output = new arch\component\template\AttributeList($this->context, [$fields, $record]);
        $output->setViewArg(lcfirst($this->getRecordKeyName()));
        $output->setRenderTarget($this->view);

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method1 = 'define'.ucfirst($field).'Field';
                $method2 = 'override'.ucfirst($field).'Field';

                if(method_exists($this, $method2)) {
                    $output->setField($field, function($list, $key) use($method2, $field) {
                        if(false === $this->{$method2}($list, 'details')) {
                            $list->addField($key);
                        }
                    });
                } else if(method_exists($this, $method1)) {
                    $output->setField($field, function($list, $key) use($method1, $field) {
                        if(false === $this->{$method1}($list, 'details')) {
                            $list->addField($key);
                        }
                    });
                }
            }
        }

        return $output;
    }

    public function generateCollectionList(array $fields, $collection=null) {
        $nameKey = $this->getRecordNameField();

        if(empty($fields)) {
            $fields[] = $nameKey;
        }

        $output = new arch\component\template\CollectionList($this->context, [$fields, $collection]);
        $output->setViewArg(lcfirst($this->getRecordKeyName()).'List');
        $output->setRenderTarget($this->view);

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method1 = 'define'.ucfirst($field).'Field';
                $method2 = 'override'.ucfirst($field).'Field';

                if(method_exists($this, $method2)) {
                    $output->setField($field, function($list, $key) use($method2, $field, $nameKey) {
                        if(false === $this->{$method2}($list, 'list')) {
                            $list->addField($key);
                        }
                    });
                } else if(method_exists($this, $method1)) {
                    $output->setField($field, function($list, $key) use($method1, $field, $nameKey) {
                        if($field == $nameKey) {
                            return $this->_autoDefineNameKeyField($field, $list, 'list');
                        } else {
                            if(false === $this->{$method1}($list, 'list')) {
                                $list->addField($key);
                            }
                        }
                    });
                } else if($field == $nameKey) {
                    $output->setField($field, function($list, $key) use($field) {
                        return $this->_autoDefineNameKeyField($field, $list, 'list');
                    });
                }
            }
        }

        return $output;
    }

// Helpers
    protected function _getDirectoryKeyName() {
        if($this->_directoryKeyName) {
            return $this->_directoryKeyName;
        }

        if(static::DIRECTORY_KEY_NAME) {
            $this->_directoryKeyName = static::DIRECTORY_KEY_NAME;
        } else {
            $parts = $this->context->location->getControllerParts();
            $this->_directoryKeyName = array_pop($parts);
        }

        return $this->_directoryKeyName;
    }

    protected function _getActionRequest($action, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]) {
        $output = clone $this->context->location;
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

        return $this->uri->directoryRequest($output, $redirFrom, $redirTo);
    }

    protected function _normalizeFieldOutput($field, $value) {
        if($value instanceof core\time\IDate) {
            return $this->format->userDateTime($value, 'short');
        }

        return $value;
    }


    protected function _generateAction($callback) {
        return (new arch\Action($this->context, function($action) use($callback) {
                if(null !== ($pre = $this->onActionDispatch($action))) {
                    return $pre;
                }

                return core\lang\Callback::factory($callback)->invoke();
            }))
            ->setDefaultAccess($this->getDefaultAccess());
    }
}