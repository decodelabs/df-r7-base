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

class DataObject extends core\collection\Tree implements IData {

    protected $_type;
    protected $_request;

    public function __construct(string $type, core\collection\ITree $data, $callback=null) {
        $this->setType($type);

        if($callback) {
            core\lang\Callback::call($callback, $data);
        }

        $this->_collection = $data->_collection;
    }

    public function setType(string $type) {
        $this->_type = $type;
        return $this;
    }

    public function getType(): string {
        return $this->_type;
    }

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

        $output['ty'] = $this->_type;

        if($this->_request) {
            $output['re'] = $this->_request;
        }

        return $output;
    }

    protected function _setUnserializedValues(array $values) {
        parent::_setUnserializedValues($values);

        $this->_type = $values['ty'] ?? 'object';
        $this->_request = $values['re'] ?? null;
    }

// Dump
    public function getDumpProperties() {
        $output = parent::getDumpProperties();

        array_unshift(
            $output,
            new core\debug\dumper\Property('type', $this->_type, 'private'),
            new core\debug\dumper\Property('request', $this->_request, 'private')
        );

        return $output;
    }
}