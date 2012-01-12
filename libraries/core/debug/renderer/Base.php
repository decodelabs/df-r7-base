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
    
    public function __construct(core\debug\IContext $context) {
        $this->_context = $context;
    }
    
    
    protected function _getNodeLocation(core\debug\INode $node) {
        return $this->_normalizeLocation($node->getFile(), $node->getLine());
    }
    
    protected function _normalizeLocation($file, $line) {
        return $this->_normalizeFilePath($file).' : '.$line;
    }
    
    protected function _normalizeFilePath($file) {
        foreach(df\Launchpad::$loader->getLocations() as $key => $match) {
            if(substr($file, 0, $len = strlen($match)) == $match) {
                $file = $key.'://'.substr(str_replace('\\', '/', $file), $len + 1);
                break;
            }
        }
        
        return $file;
    }
}
