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
    use aura\view\TView_CascadingHelperProvider;
    use arch\TDirectoryAccessLock;
    use arch\TOptionalDirectoryAccessLock;

    const TITLE = null;
    const ICON = null;

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
            throw core\Error::ENotFound('Scaffold could not be found for '.$context->location);
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

    public function getRegistryObjectKey(): string {
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

            if(isset($this->request->query->{$var})) {
                $output[] = $this->html->hidden($var, $this->request->query[$var]);
            }
        }

        return $output;
    }


// Loaders
    public function loadNode() {
        $node = $this->context->request->getNode();
        $method = lcfirst($node).$this->context->request->getType().'Node';

        if(!method_exists($this, $method)) {
            $method = lcfirst($node).'Node';

            if(!method_exists($this, $method)) {
                $method = 'build'.ucfirst($node).'DynamicNode';

                if(method_exists($this, $method)) {
                    $node = $this->{$method}();

                    if($node instanceof arch\node\INode) {
                        return $node;
                    }
                }

                if($this instanceof ISectionProviderScaffold && ($node = $this->loadSectionNode())) {
                    return $node;
                }

                throw core\Error::{'arch/scaffold/ENodeNotFound,ENotFound'}(
                    'Scaffold at '.$this->context->location.' cannot provide node '.$node
                );
            }
        }

        return $this->_generateNode([$this, $method]);
    }

    public function onNodeDispatch(arch\node\INode $node) {}

    public function loadComponent($name, array $args=null) {
        $keyName = $this->getDirectoryKeyName();
        $origName = $name;

        if(substr($name, 0, strlen($keyName)) == ucfirst($keyName)) {
            $name = substr($name, strlen($keyName));
        }

        $method = 'generate'.$name.'Component';

        if(!method_exists($this, $method) && $origName !== $name) {
            $method = 'generate'.$origName.'Component';
            $activeName = $origName;
        } else {
            $activeName = $name;
        }

        if(method_exists($this, $method)) {
            return new arch\scaffold\component\Generic($this, $activeName, $args);
        }

        $method = 'build'.$name.'Component';

        if(!method_exists($this, $method) && $origName !== $name) {
            $method = 'build'.$origName.'Component';
        }

        if(method_exists($this, $method)) {
            $output = $this->{$method}($args);

            if(!$output instanceof arch\IComponent) {
                throw core\Error::{'arch/component/ENotFound,ENotFound'}(
                    'Scaffold at '.$this->context->location.' attempted but failed to provide component '.$origName
                );
            }

            return $output;
        }

        throw core\Error::{'arch/component/ENotFound,ENotFound'}(
            'Scaffold at '.$this->context->location.' cannot provide component '.$origName
        );
    }

    public function loadFormDelegate($name, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, $id) {
        $keyName = $this->getDirectoryKeyName();
        $origName = $name;

        if(substr($name, 0, strlen($keyName)) == ucfirst($keyName)) {
            $name = substr($name, strlen($keyName));
        }

        $method = 'build'.$name.'FormDelegate';

        if(!method_exists($this, $method)) {
            throw core\Error::{'arch/node/ENotFound,ENotFound'}(
                'Scaffold at '.$this->context->location.' cannot provide form delegate '.$origName
            );
        }

        $output = $this->{$method}($state, $event, $id);

        if(!$output instanceof arch\node\IDelegate) {
            throw core\Error::{'arch/node/ENotFound,ENotFound'}(
                'Scaffold at '.$this->context->location.' attempted but failed to provide form delegate '.$origName
            );
        }

        return $output;
    }

    public function loadMenu($name, $id) {
        $method = 'generate'.ucfirst($name).'Menu';

        if(!method_exists($this, $method)) {
            throw core\Error::{'arch/navigation/ENotFound,ENotFound'}(
                'Scaffold at '.$this->context->location.' could not provider menu '.$name
            );
        }

        return new arch\scaffold\navigation\Menu($this, $name, $id);
    }


// Directory
    public function getDirectoryTitle() {
        if(static::TITLE) {
            return $this->_(static::TITLE);
        }

        return $this->format->name($this->getDirectoryKeyName());
    }

    public function getDirectoryIcon() {
        if(static::ICON) {
            return static::ICON;
        }

        return $this->getDirectoryKeyName();
    }

    public function getDirectoryKeyName() {
        if($this->_directoryKeyName === null) {
            $parts = $this->context->location->getControllerParts();
            $this->_directoryKeyName = array_pop($parts);
        }

        return $this->_directoryKeyName;
    }


// Generators
    public function generateAttributeList(array $fields, $record=true) {
        if($record === true) {
            $record = $this->getRecord();
        }

        $output = new arch\component\AttributeList($this->context, [$fields, $record]);
        $output->setViewArg(lcfirst($this->getRecordKeyName()));
        $output->setRenderTarget($this->view);
        $spacerIterator = 0;

        foreach($output->getFields() as $field => $enabled) {
            /*
            if(substr($field, 0, 2) == '--') {
                $output->setField('divider'.($spacerIterator++), function($list, $key) use($field) {
                    $list->addField($key, function($data, $context) use($field) {
                        if($field == '--') {
                            $context->addDivider();
                        } else {
                            $context->setDivider(ucfirst(substr($field, 2)));
                        }

                        $context->skipRow();
                    });
                });

                continue;
            }
            */

            if($enabled === true) {
                $method1 = 'define'.ucfirst($field).'Field';
                $method2 = 'override'.ucfirst($field).'Field';
                $method = null;

                if(method_exists($this, $method2)) {
                    $method = $method2;
                } else if(method_exists($this, $method1)) {
                    $method = $method1;
                }

                $output->setField($field, function($list, $key) use($method, $field) {
                    if($method) {
                        $ops = $this->{$method}($list, 'details');
                    } else {
                        $ops = false;
                    }

                    if($ops === false) {
                        $list->addField($key, function($data, $renderContext) {
                            $key = $renderContext->getField();
                            $value = null;

                            if(is_array($data)) {
                                if(isset($data[$key])) {
                                    $value = $data[$key];
                                } else {
                                    $value = null;
                                }
                            } else if($data instanceof \ArrayAccess) {
                                $value = $data[$key];
                            } else if(is_object($data)) {
                                if(method_exists($data, '__get')) {
                                    $value = $data->__get($key);
                                } else if(method_exists($data, 'get'.ucfirst($key))) {
                                    $value = $data->{'get'.ucfirst($key)}();
                                }
                            }

                            if($value instanceof aura\view\IDeferredRenderable
                            && $this->view) {
                                $value->setRenderTarget($this->view);
                            }

                            return $value;
                        });
                    }
                });
            }
        }

        return $output;
    }

    public function generateCollectionList(array $fields, $collection=null) {
        $nameKey = $this->getRecordNameField();

        if(empty($fields) || (count($fields) == 1 && current($fields) == 'actions')) {
            array_unshift($fields, $nameKey);
        }

        $output = new arch\component\CollectionList($this->context, [$fields, $collection]);
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
    protected function _getNodeRequest($node, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]) {
        $output = clone $this->context->location;
        $output->setNode($node);
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
            if(!in_array($var, $propagationFilter) && isset($this->request->query->{$var})) {
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
            return $this->format->userDateTime($value, $value->hasTime() ? 'short' : 'medium');
        }

        return $value;
    }


    protected function _generateNode($callback) {
        return (new arch\node\Base($this->context, function($node) use($callback) {
                if(null !== ($pre = $this->onNodeDispatch($node))) {
                    return $pre;
                }

                return core\lang\Callback::factory($callback)->invoke();
            }))
            ->setDefaultAccess($this->getDefaultAccess())
            ->setAccessSignifiers(...$this->getAccessSignifiers());
    }
}
