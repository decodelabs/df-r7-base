<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\schema\field;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\opal;

abstract class Base implements opal\rdbms\schema\IField, Dumpable
{
    use opal\schema\TField;
    public const DEFAULT_VALUE = '';

    protected $_type;
    protected $_sqlVariant;
    protected $_nullConflictClause;
    protected $_collation;


    public static function factory(opal\rdbms\schema\ISchema $schema, $type, $name, array $args)
    {
        $type = strtolower((string)$type);

        switch ($type) {
            case 'bool':
            case 'boolean':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
                $classType = 'Integer';
                break;

            case 'float':
            case 'double':
            case 'double precision':
            case 'real':
            case 'decimal':
                $classType = 'FloatingPoint';
                break;

            case 'char':
            case 'varchar':
                $classType = 'Char';
                break;

            case 'tinytext':
            case 'smalltext':
            case 'text':
            case 'mediumtext':
            case 'longtext':
                $classType = 'Text';
                break;

            case 'tinyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
                $classType = 'Blob';
                break;

            case 'binary':
            case 'varbinary':
                $classType = 'Binary';
                break;

            case 'date':
            case 'datetime':
            case 'time':
            case 'year':
                $classType = 'DateTime';
                break;

            case 'timestamp':
                $classType = 'Timestamp';
                break;

            case 'enum':
            case 'set':
                $classType = 'Set';
                break;

            default:
                $classType = ucfirst($type);
                break;
        }

        $class = 'df\\opal\\rdbms\\schema\\field\\' . $classType;

        if (!class_exists($class)) {
            throw Exceptional::UnexpectedValue(
                'Field type ' . $type . ' is not currently supported'
            );
        }

        return new $class($schema, $type, $name, $args);
    }

    public function __construct(opal\rdbms\schema\ISchema $schema, $type, $name, array $args)
    {
        $this->_setName($name);
        $this->_sqlVariant = $schema->getSqlVariant();
        $this->_type = $type;

        if (method_exists($this, '_init')) {
            $this->_init(...$args);
        }
    }

    public function getType(): string
    {
        return $this->_type;
    }

    public function getSqlVariant()
    {
        return $this->_sqlVariant;
    }


    public function setNullConflictClause($clause)
    {
        if (is_string($clause) && !is_numeric($clause)) {
            switch (strtoupper($clause)) {
                case 'ROLLBACK':
                    $clause = opal\schema\IConflictClause::ROLLBACK;
                    break;

                case 'ABORT':
                    $clause = opal\schema\IConflictClause::ABORT;
                    break;

                case 'FAIL':
                    $clause = opal\schema\IConflictClause::FAIL;
                    break;

                case 'IGNORE':
                    $clause = opal\schema\IConflictClause::IGNORE;
                    break;

                case 'REPLACE':
                    $clause = opal\schema\IConflictClause::REPLACE;
                    break;

                default:
                    $clause = null;
            }
        }

        switch ((int)$clause) {
            case opal\schema\IConflictClause::ROLLBACK:
            case opal\schema\IConflictClause::ABORT:
            case opal\schema\IConflictClause::FAIL:
            case opal\schema\IConflictClause::IGNORE:
            case opal\schema\IConflictClause::REPLACE:
                break;

            default:
                $clause = null;
        }

        $this->_nullConflictClause = $clause;
        return $this;
    }

    public function getNullConflictClauseId()
    {
        return $this->_nullConflictClause;
    }

    public function getNullConflictClauseName()
    {
        switch ($this->_nullConflictClause) {
            case opal\schema\IConflictClause::ROLLBACK:
                return 'ROLLBACK';

            case opal\schema\IConflictClause::ABORT:
                return 'ABORT';

            case opal\schema\IConflictClause::FAIL:
                return 'FAIL';

            case opal\schema\IConflictClause::IGNORE:
                return 'IGNORE';

            case opal\schema\IConflictClause::REPLACE:
                return 'REPLACE';
        }
    }


    public function getDefaultNonNullValue()
    {
        if (null !== ($value = $this->getDefaultValue())) {
            return $this->getDefaultValue();
        }

        return static::DEFAULT_VALUE;
    }


    public function setCollation($collation)
    {
        if ($collation != $this->_collation) {
            $this->_hasChanged = true;
        }

        $this->_collation = $collation;
        return $this;
    }

    public function getCollation()
    {
        return $this->_collation;
    }

    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (\Throwable $e) {
            return $this->_name . ' ' . strtoupper($this->_type);
        }
    }

    // Ext. serialize
    public function toStorageArray()
    {
        return $this->_getBaseStorageArray();
    }

    protected function _getBaseStorageArray()
    {
        return array_merge(
            [
                'typ' => $this->_type,
                'var' => $this->_sqlVariant,
                'ncc' => $this->_nullConflictClause,
                'col' => $this->_collation
            ],
            $this->_getGenericStorageArray()
        );
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->toString();
    }
}
