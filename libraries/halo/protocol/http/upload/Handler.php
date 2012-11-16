<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\upload;

use df;
use df\core;
use df\halo;
    
class Handler implements halo\protocol\http\IUploadHandler {

    use core\mime\TAcceptTypeProcessor;

    protected $_files = array();
    protected $_extensions = array();
    protected $_maxSize;

    public function __construct() {
        if(empty($_FILES)) {
            return;
        }

        foreach($_FILES as $key => $set) {
            if(is_array($set['name'])) {
                $sets = array();

                foreach($set as $fileVar => $array) {
                    $array = $this->_flattenArray($array, $key);

                    foreach($array as $field => $value) {
                        $sets[$field][$fileVar] = $value;
                    }
                }

                foreach($sets as $key => $set) {
                    if(!empty($set['name'])) {
                        $this->_files[$key] = $set;
                    }
                }
            } else {
                if(!empty($set['name'])) {
                    $this->_files[$key] = $set;
                }
            }
        }

        $this->setMaxFileSize('128mb');
    }

    protected function _flattenArray(array $array, $currentKey='') {
        $output = array();
        
        foreach($array as $key => $var) {
            $thisKey = $currentKey.'['.$key.']';
            //$thisKey = $currentKey.'.'.$key;
            
            if(is_array($var)) {
                foreach($this->_flattenArray($var, $thisKey) as $childKey => $value) {
                    $output[$childKey] = $value;    
                }
            } else {
                $output[$thisKey] = $var;    
            }
        }
        
        return $output;
    }

    public function setAllowedExtensions(array $extensions) {
        $this->_extensions = array();
        return $this->addAllowedExtensions($extensions);
    }

    public function addAllowedExtensions(array $extensions) {
        foreach($extensions as $ext) {
            $this->_extensions[] = $this->_normalizeExtension($ext);
        }

        return $this;
    }

    public function getAllowedExtensions() {
        return $this->_extensions;
    }

    public function isExtensionAllowed($extension) {
        if(empty($this->_extensions)) {
            return true;
        }

        return in_array($this->_normalizeExtension($extension), $this->_extensions);
    }

    protected function _normalizeExtension($ext) {
        return ltrim(trim(strtolower($ext)), '.');
    }



    public function setMaxFileSize($size) {
        $size = core\unit\FileSize::factory($size);
        $postMaxSize = core\unit\FileSize::factory(ini_get('post_max_size'));

        if($postMaxSize->getValue() > -1 && $postMaxSize->getMegabytes() < $size->getMegabytes()) {
            $size = $postMaxSize;
        }

        $uploadMaxSize = core\unit\FileSize::factory(ini_get('upload_max_filesize'));

        if($uploadMaxSize->getValue() > -1 && $uploadMaxSize->getMegabytes() < $size->getMegabytes()) {
            $size = $uploadMaxSize;
        }

        $this->_maxSize = $size;
        return $this;
    }

    public function getMaxFileSize() {
        return $this->_maxSize;
    }

    public function uploadAll($destination, core\collection\IInputTree $inputCollection, $conflictAction=IUploadFile::RENAME) {
        foreach($this as $file) {
            $file->upload($destination, $inputCollection->{$file->getFieldName()}, $conflictAction);
        }

        return $this;
    }


// Countable
    public function count() {
        return count($this->_files);
    }
    
// Iterator
    public function getIterator() {
        $output = array();
        
        foreach($this->_files as $key => $set) {
            $output[$key] = new File($this, $key, $set); 
        }
        
        return new \ArrayIterator($output);
    }
    
// Array access
    public function offsetSet($offset, $value) {}
    
    public function offsetGet($offset) {
        if(isset($this->_files[$offset])) {
            return new File($this, $offset, $this->_files[$offset]);
        }
    }
    
    public function offsetExists($offset) {
        return isset($this->_files[$offset]);
    }
    
    public function offsetUnset($offset) {
        unset($this->_files[$offset]);
    }
}