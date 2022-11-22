<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\schema;

use df\opal;

class Introspector
{
    protected static $_recordFields = [];
    protected static $_primaryFields = [];
    protected static $_fieldProcessors = [];

    // Record fields
    public static function getRecordFields(opal\query\IAdapter $adapter, array $inputFields = null)
    {
        if ($inputFields !== null) {
            return $inputFields;
        }

        $id = $adapter->getQuerySourceId();

        if (!isset(self::$_recordFields[$id])) {
            if ($adapter instanceof opal\query\IIntegralAdapter) {
                self::$_recordFields[$id] = array_keys($adapter->getQueryAdapterSchema()->getFields());
            } else {
                self::$_recordFields[$id] = [];
            }
        }

        return self::$_recordFields[$id];
    }


// Primary fields
    public static function getPrimaryFields(opal\query\IAdapter $adapter = null)
    {
        if ($adapter === null) {
            return null;
        }

        $id = $adapter->getQuerySourceId();

        if (!isset(self::$_primaryFields[$id])) {
            self::$_primaryFields[$id] = false;

            if ($adapter instanceof opal\query\IIntegralAdapter) {
                $index = $adapter->getQueryAdapterSchema()->getPrimaryIndex();

                if ($index) {
                    self::$_primaryFields[$id] = array_keys($index->getFields());
                }
            } elseif ($adapter instanceof opal\query\INaiveIntegralAdapter) {
                $index = $adapter->getPrimaryIndex();

                if ($index) {
                    self::$_primaryFields[$id] = array_keys($index->getFields());
                }
            }
        }

        return self::$_primaryFields[$id] === false ? null : self::$_primaryFields[$id];
    }

// Field processors
    public static function getFieldProcessors(opal\query\IAdapter $adapter = null, array $filter = null)
    {
        if ($adapter === null) {
            return [];
        }

        $id = $adapter->getQuerySourceId();

        if (!isset(self::$_fieldProcessors[$id])) {
            self::$_fieldProcessors[$id] = [];

            if ($adapter instanceof opal\query\IIntegralAdapter) {
                self::$_fieldProcessors[$id] = $adapter->getQueryResultValueProcessors();
            }
        }

        $output = self::$_fieldProcessors[$id];

        if ($filter !== null) {
            $output = array_intersect_key($output, array_flip($filter));
        }

        return $output;
    }

    public static function getFieldProcessor(opal\query\IAdapter $adapter, $field)
    {
        $processors = self::getFieldProcessors($adapter);

        if (isset($processors[$field])) {
            return $processors[$field];
        }

        return null;
    }
}
