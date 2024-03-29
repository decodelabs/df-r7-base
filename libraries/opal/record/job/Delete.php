<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\job;

use DecodeLabs\Exceptional;
use df\mesh;

use df\opal;

class Delete extends mesh\job\Base implements opal\record\IJob
{
    use opal\record\TJob;

    public function __construct(opal\record\IRecord $record)
    {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }

    public function getRecordJobName()
    {
        return 'Delete';
    }

    public function execute()
    {
        if ($this->_record->isNew()) {
            return $this;
        }

        $adapter = $this->getAdapter();

        if (!$adapter instanceof opal\query\IEntryPoint) {
            throw Exceptional::Logic(
                'Adapter is not capable of creating queries',
                null,
                $adapter
            );
        }

        $query = $adapter->delete();
        $keySet = $this->_record->getOriginalPrimaryKeySet();

        if ($this->_record instanceof opal\record\ILocationalRecord) {
            $query->inside($this->_record->getQueryLocation());
        }

        if (!$keySet->isNull()) {
            foreach ($keySet->toArray() as $field => $value) {
                $query->where($field, '=', $value);
            }
        } else {
            $order = false;

            foreach ($this->_record->getOriginalValuesForStorage() as $key => $value) {
                if (!$order) {
                    $query->limit(1)->orderBy($key);
                    $order = true;
                }

                $query->where($key, '=', $value);
            }
        }

        $query->execute();
        $this->_record->makeNew();

        return $this;
    }
}
