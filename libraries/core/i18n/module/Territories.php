<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

class Territories extends Base implements ITerritoriesModule
{
    const MODULE_NAME = 'territories';

    public function getName($id)
    {
        $this->_loadData();
        $id = strtoupper($id);

        if (isset($this->_data[$id])) {
            return $this->_data[$id];
        }

        return $id;
    }

    public function getList(array $ids=null)
    {
        $this->_loadData();
        $output = $this->_data;

        if ($ids !== null) {
            $output = array_intersect_key($output, array_flip(array_values($ids)));
        }

        return $output;
    }

    public function getCodeList()
    {
        $this->_loadData();
        return array_keys($this->_data);
    }

    public function isValidId($id)
    {
        $this->_loadData();
        return isset($this->_data[$id]);
    }
}
