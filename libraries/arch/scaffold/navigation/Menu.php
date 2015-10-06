<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\navigation;

use df;
use df\core;
use df\arch;

class Menu extends arch\navigation\menu\Base {

    protected $_scaffold;
    protected $_name;

    public function __construct(arch\scaffold\IScaffold $scaffold, $name, $id) {
        $this->_scaffold = $scaffold;
        $this->_name = $name;
        parent::__construct($scaffold->getContext(), $id);
    }

    protected function _createEntries(arch\navigation\IEntryList $entryList) {
        $method = 'generate'.ucfirst($this->_name).'Menu';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($entryList);
        }
    }

    protected function _getStorageArray() {
        return array_merge(parent::_getStorageArray(), [
            'name' => $this->_name
        ]);
    }

    protected function _setStorageArray(array $data) {
        parent::_setStorageArray($data);

        $this->_name = $data['name'];

        if(!$this->_scaffold) {
            $this->_scaffold = arch\scaffold\Base::factory($this->context);
        }
    }
}