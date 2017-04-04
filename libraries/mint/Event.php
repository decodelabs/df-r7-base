<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint;

use df;
use df\core;
use df\mint;
use df\mesh;

class Event extends core\collection\Tree implements IEvent {

    protected $_source;
    protected $_action;

    public function __construct(string $source, string $action, core\collection\ITree $data) {
        $this->_source = $source;
        $this->_action = $action;

        $this->_collection = $data->_collection;
    }

    public function getSource(): string {
        return $this->_source;
    }

    public function getAction(): string {
        return $this->_action;
    }

    public function getEntityLocator() {
        return new mesh\entity\Locator('mint://'.$this->_source.'/Event');
    }
}