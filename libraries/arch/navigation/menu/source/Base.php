<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu\source;

use df;
use df\core;
use df\arch;

abstract class Base implements arch\navigation\menu\ISource, arch\IContextAware {
    
    use arch\TContextAware;

    public static function loadAll(arch\IContext $context) {
        $output = array();
        
        foreach(df\Launchpad::$loader->lookupFileList('arch/navigation/menu/source', ['php']) as $baseName => $path) {
            $name = substr($baseName, 0, -4);
            
            if($name === 'Base' || $name === '_manifest') {
                continue;
            }
            
            try {
                $source = self::factory($context, $name);
            } catch(arch\navigation\SourceNotFoundException $e) {
                continue;
            }
            
            $output[$source->getName()] = $source;
        }
        
        ksort($output);
        return $output;
    }
    
    public static function factory(arch\IContext $context, $type) {
        $class = 'df\\arch\\navigation\\menu\\source\\'.ucfirst($type);
        
        if(!class_exists($class)) {
            throw new arch\navigation\SourceNotFoundException(
                'Source type '.$type.' could not be found'
            );
        }
        
        return new $class($context);
    }
    
    public function __construct(arch\IContext $context) {
        $this->_context = $context;
    }
    
    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
    
    public function getDisplayName() {
        return $this->getName();
    }
}

