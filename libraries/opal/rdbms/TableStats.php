<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms;

use df\core;

class TableStats implements ITableStats
{
    use core\collection\TAttributeContainer;

    protected $_version;
    protected $_rowCount;
    protected $_size;
    protected $_indexSize;
    protected $_creationDate;
    protected $_schemaUpdateDate;

    public function setVersion($version)
    {
        $this->_version = $version;
        return $this;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function setRowCount($rows)
    {
        $this->_rowCount = $rows;
        return $this;
    }

    public function getRowCount()
    {
        return $this->_rowCount;
    }

    public function setSize($size)
    {
        $this->_size = $size;
        return $this;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function setIndexSize($size)
    {
        $this->_indexSize = $size;
        return $this;
    }

    public function getIndexSize()
    {
        return $this->_indexSize;
    }

    public function setCreationDate($date)
    {
        $this->_creationDate = core\time\Date::normalize($date);
        return $this;
    }

    public function getCreationDate()
    {
        return $this->_creationDate;
    }

    public function setSchemaUpdateDate($date)
    {
        $this->_schemaUpdateDate = core\time\Date::normalize($date);
        return $this;
    }

    public function getSchemaUpdateDate()
    {
        return $this->_schemaUpdateDate;
    }
}
