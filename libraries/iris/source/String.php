<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\source;

use df;
use df\core;
use df\iris;
    
class String implements iris\ISource {

    protected $_string;
    protected $_uri;

    public function __construct($uri, $string, $encoding=null) {
        $this->_uri = SourceUri::factory($uri);
        $this->_string = new core\string\Manipulator($string, $encoding);
    }

    public function getSourceUri() {
        return $this->_uri;
    }

    public function getEncoding() {
        return $this->_string->getEncoding();
    }

    public function substring($start, $length=1) {
        return $this->_string->getSubstring($start, $length);
    }
}