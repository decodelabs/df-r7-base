<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\request;

use df;
use df\core;
use df\halo;

class HeaderCollection extends core\collection\HeaderMap implements halo\protocol\http\IRequestHeaderCollection {
    
    use halo\protocol\http\THeaderCollection;
    
    public function reset() {
        $this->clear();
        $this->_httpVersion = '1.1';
        
        return $this;
    }
    
    public function getDumpProperties() {
        $output = parent::getDumpProperties();
        
        array_unshift($output, new core\debug\dumper\Property(
            'httpVersion', $this->_httpVersion, 'private'
        ));
        
        return $output;
    }
}