<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\source;

use df;
use df\core;
use df\iris;
use df\flex;

class String implements iris\ISource {

    protected $_string;
    protected $_uri;

    public function __construct($string, $uri=null, $encoding=null) {
        if($uri === null) {
            $uri = 'dynamic://'.hash('crc32', $string);
        }

        $this->_uri = iris\SourceUri::factory($uri);
        $this->_string = new flex\Text($string, $encoding);
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