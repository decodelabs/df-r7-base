<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
use df\mesh;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;

class Transaction extends mesh\job\Transaction implements ITransaction, Dumpable
{
    protected $_source;

    public function __construct($source=false)
    {
        parent::__construct(true);

        if ($source === false) {
            $source = null;
        } elseif (!$source) {
            throw Glitch::EInvalidArgument(
                'Implicit source transaction has no source'
            );
        }

        $this->_source = $source;
    }

    public function select(...$fields)
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginSelect($fields);

        if ($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function selectDistinct(...$fields)
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginSelect($fields, true);

        if ($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function countAll()
    {
        if ($this->_source === null) {
            throw Glitch::ERuntime(
                'Cannot countAll without implicit source'
            );
        }

        return $this->select()->count();
    }

    public function countAllDistinct()
    {
        if ($this->_source === null) {
            throw Glitch::ERuntime(
                'Cannot countAll without implicit source'
            );
        }

        return $this->selectDistinct()->count();
    }

    public function union(...$fields)
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginUnion()
            ->with($fields);

        if ($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function fetch()
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginFetch();

        if ($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function insert($row)
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginInsert($row);

        if ($this->_source !== null) {
            $output = $output->into($this->_source);
        }

        return $output;
    }

    public function batchInsert($rows=[])
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginBatchInsert($rows);

        if ($this->_source !== null) {
            $output = $output->into($this->_source);
        }

        return $output;
    }

    public function replace($row)
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginReplace($row);

        if ($this->_source !== null) {
            $output = $output->in($this->_source);
        }

        return $output;
    }

    public function batchReplace($rows=[])
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginBatchReplace($rows);

        if ($this->_source !== null) {
            $output = $output->in($this->_source);
        }

        return $output;
    }

    public function update(array $valueMap=null)
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginUpdate($valueMap);

        if ($this->_source !== null) {
            $output = $output->in($this->_source);
        }

        return $output;
    }

    public function delete()
    {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginDelete();

        if ($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function newTransaction(): mesh\job\ITransaction
    {
        if ($this->_source !== null) {
            return new self($this->_source);
        } else {
            return new self();
        }
    }


    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        $adapters = [];

        foreach ($this->_adapters as $adapter) {
            $adapters[] = $adapter->getQuerySourceDisplayName();
        }

        yield 'values' => $adapters;
    }
}
