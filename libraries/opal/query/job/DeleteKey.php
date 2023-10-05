<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\job;

use df\mesh;
use df\opal;

class DeleteKey extends mesh\job\Base implements opal\query\IKeyBasedJob, opal\query\IFilterKeyBasedJob
{
    use mesh\job\TAdapterAwareJob;

    protected $_keys = [];
    protected $_filterKeys = [];

    public function __construct(opal\query\IAdapter $adapter, array $keys)
    {
        $this->_keys = $keys;
        $this->_adapter = $adapter;

        $this->_setId(implode(opal\record\PrimaryKeySet::COMBINE_SEPARATOR, $keys));
    }


    // Keys
    public function setKeys(array $keys)
    {
        $this->_keys = [];
        return $this->addKeys($keys);
    }

    public function addKeys(array $keys)
    {
        foreach ($keys as $key => $value) {
            $this->addKey($key, $value);
        }

        return $this;
    }

    public function addKey($key, $value)
    {
        $this->_keys[$key] = $value;
        return $this;
    }

    public function getKeys()
    {
        return $this->_keys;
    }


    // Filter keys
    public function setFilterKeys(array $filterKeys)
    {
        $this->_filterKeys = [];
        return $this->addFilterKeys($filterKeys);
    }

    public function addFilterKeys(array $keys)
    {
        foreach ($keys as $key => $value) {
            $this->addFilterKey($key, $value);
        }

        return $this;
    }

    public function addFilterKey($key, $value)
    {
        $this->_filterKeys[$key] = $value;
        return $this;
    }

    public function getFilterKeys()
    {
        return $this->_filterKeys;
    }

    public function execute()
    {
        $query = $this->_adapter->delete();

        foreach ($this->_keys as $key => $value) {
            $query->where($key, '=', $value);
        }

        if (!empty($this->_filterKeys)) {
            foreach ($this->_filterKeys as $key => $value) {
                $query->where($key, '!=', $value);
            }
        }

        $query->execute();
        return $this;
    }
}
