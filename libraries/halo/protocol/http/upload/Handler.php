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
                    $this->_files[$key] = $set;
                }
            } else {
                $this->_files[$key] = $set;
            }
        }

        $this->setMaxFileSize('128mb');
    }
}