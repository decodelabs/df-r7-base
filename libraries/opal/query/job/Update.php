<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\job;

use df\mesh;
use df\opal;

class Update extends mesh\job\Base
{
    use mesh\job\TAdapterAwareJob;

    protected $_primaryKeySet;
    protected $_values;

    public function __construct(opal\query\IAdapter $adapter, opal\record\IPrimaryKeySet $primaryKeySet, array $values)
    {
        $this->_primaryKeySet = $primaryKeySet;
        $this->_values = $values;
        $this->_adapter = $adapter;

        $this->_setId(opal\record\Base::extractRecordId($primaryKeySet));
    }

    public function setValues(array $values)
    {
        $this->_values = $values;
        return $this;
    }

    public function getValues()
    {
        return $this->_values;
    }

    public function execute()
    {
        if ($this->_primaryKeySet->isNull()) {
            return $this;
        }

        $query = $this->_adapter->update($this->_values);

        foreach ($this->_primaryKeySet->toArray() as $field => $value) {
            $query->where($field, '=', $value);
        }

        $query->execute();

        return $this;
    }
}
