<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

abstract class Struct implements IStruct {
    
    public function __construct(array $data=null) {
        if($data) {
            $this->import($data);
        }
    }

    public function import(array $data) {
        foreach($data as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }
}