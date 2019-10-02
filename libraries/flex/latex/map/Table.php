<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;

class Table extends iris\map\Node implements flex\latex\ITable
{
    use flex\latex\TContainerNode;
    use flex\latex\TReferable;
    use flex\latex\TCaptioned;
    use flex\latex\TPlacementAware;
    use flex\latex\TListedNode;

    protected $_columns = [];

    public function addColumn(flex\latex\IColumn $column)
    {
        $this->_columns[] = $column;
        return $this;
    }

    public function getColumns()
    {
        return $this->_columns;
    }

    public function addRow(array $row)
    {
        return $this->push($row);
    }

    public function isFirstRowHead()
    {
        foreach ($this->_collection as $child) {
            if (is_array($child)) {
                foreach ($child as $cell) {
                    if ($cell->getType() != 'cell') {
                        continue;
                    }

                    if (!$cell->containsOnlySpan() && !$cell->isEmpty()) {
                        return false;
                    }
                }

                return true;
            }
        }
    }

    public function isFirstColumnHead()
    {
        foreach ($this->_collection as $child) {
            if (!is_array($child) || !isset($child[0])) {
                continue;
            }

            if (!$child[0]->containsOnlySpan() && !$child[0]->isEmpty()) {
                return false;
            }
        }

        return true;
    }
}
