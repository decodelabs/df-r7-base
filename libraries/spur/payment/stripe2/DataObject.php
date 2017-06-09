<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2;

use df;
use df\core;
use df\spur;
use df\mint;

class DataObject extends spur\DataObject implements IDataObject {

    protected $_request;

    public function setRequest(IRequest $request) {
        $this->_request = $request;
        return $this;
    }

    public function getRequest(): ?IRequest {
        return $this->_request;
    }


// Serialize
    protected function _getSerializeValues() {
        $output = parent::_getSerializeValues();

        if($this->_request) {
            $output['re'] = $this->_request;
        }

        return $output;
    }

    protected function _setUnserializedValues(array $values) {
        parent::_setUnserializedValues($values);
        $this->_request = $values['re'] ?? null;
    }

// Dump
    public function getDumpProperties() {
        $output = parent::getDumpProperties();

        array_unshift(
            $output,
            new core\debug\dumper\Property('request', $this->_request, 'private')
        );

        return $output;
    }
}
