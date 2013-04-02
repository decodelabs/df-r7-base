<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\source;

use df;
use df\core;
use df\iris;
    
class File implements iris\ISource {

    protected $_uri;
    protected $_file;

    public function __construct($uri) {
        $this->_uri = iris\SourceUri::factory($uri);
    }

    public function getSourceUri() {
        return $this->_uri;
    }

    public function getEncoding() {
        return 'utf-8';
    }

    public function substring($start, $length=1) {
        if(!$this->_file) {
            $this->_file = new core\io\channel\File($this->_uri, core\io\IMode::READ_ONLY);
        }

        $this->_file->seek($start);
        return $this->_file->readChunk($length);
    }
}