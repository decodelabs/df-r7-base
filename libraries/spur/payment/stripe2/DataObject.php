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
        $this->_collection = $data->_collection;

        if($callback) {
            core\lang\Callback::call($callback, $this);
        }
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

    public function getRequest()/*: ?IRequest*/ {
        return $this->_request;
    }
}