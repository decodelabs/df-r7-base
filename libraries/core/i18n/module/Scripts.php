<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\i18n\module;

class Scripts extends Base implements IScriptsModule
{
    public const MODULE_NAME = 'scripts';

    public function getName($id)
    {
        $this->_loadData();
        $id = ucfirst(strtolower((string)$id));

        if (isset($this->_data[$id])) {
            return $this->_data[$id];
        }

        return $id;
    }

    public function getList(array $ids = null)
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
