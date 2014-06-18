<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\renderer;

use df;
use df\core;

abstract class Base implements core\debug\IRenderer {
    
    protected $_context;
    protected $_stats = [];
    
    public function __construct(core\debug\IContext $context) {
        $this->_context = $context;
        
        
        
        $this->_stats['Date'] = gmdate('d M Y H:i:s');
        $this->_stats['Time'] = df\Launchpad::getFormattedRunningTime($context->runningTime);
        
        if($app = df\Launchpad::$application) {
            $this->_stats['Mode'] = $app->getRunMode();
        }
        
        if(function_exists('memory_get_peak_usage')) {
            $this->_stats['Memory'] = round((memory_get_peak_usage() / (1024 * 1024)), 2).'mb / '.
                round((memory_get_usage() / (1024 * 1024)), 2).'mb';
        }
            
        $this->_stats['Includes'] = count(get_included_files());
        
        if(class_exists('df\\core\\Loader', false)) {
            $this->_stats['Includes'] .= ' / '.core\Loader::getTotalIncludeMisses();
        }

        if(class_exists('df\\opal\\rdbms\\adapter\\statement\\Base', false)) {
            $this->_stats['Queries'] = \df\opal\rdbms\adapter\statement\Base::getQueryCount();
        }
        
        $caches = [];
        
        if(extension_loaded('apc')) {
            $caches[] = 'apc';
        }
        
        if(extension_loaded('eaccelerator')) {
            $caches[] = 'eaccelerator';
        }
        
        if(extension_loaded('memcache')) {
            $caches[] = 'memcache';
        }
        
        if(!empty($caches)) {
            $this->_stats['Caches'] = implode(', ', $caches);
        }
    }
    
    public function getStats() {
        return $this->_stats;
    }

    protected function _getNormalizedIncludeList() {
        $output = [];
        
        foreach(get_included_files() as $file) {
            $output[] = $this->_normalizeFilePath($file);
        }
        
        return $output;
    }
    
    protected function _getNodeLocation(core\log\INode $node) {
        return $this->_normalizeLocation($node->getFile(), $node->getLine());
    }
    
    protected function _normalizeLocation($file, $line) {
        return $this->_normalizeFilePath($file).' : '.$line;
    }
    
    protected function _normalizeFilePath($file) {
        if(!df\Launchpad::$loader) {
            return $file;
        }
        
        $locations = df\Launchpad::$loader->getLocations();
        $locations['app'] = df\Launchpad::$applicationPath;
        
        foreach($locations as $key => $match) {
            if(substr($file, 0, $len = strlen($match)) == $match) {
                $file = $key.'://'.substr(str_replace('\\', '/', $file), $len + 1);
                break;
            }
        }
        
        return $file;
    }
    
    protected function _getObjectInheritance($object) {
        if(!is_object($object)) {
            return [];
        }
        
        $reflection = new \ReflectionClass($object);
        $list = [];
        
        while($reflection = $reflection->getParentClass()) {
            $list[] = $reflection->getName();
        }
        
        return $list;
    }
}
