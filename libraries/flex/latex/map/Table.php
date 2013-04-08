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
    
class Table extends iris\map\Node implements flex\latex\ITable, core\IDumpable {

    use flex\latex\TContainerNode;
    use flex\latex\TReferable;
    use flex\latex\TCaptioned;
    use flex\latex\TPlacementAware;

    protected $_columns = array();

    public function addColumn(flex\latex\IColumn $column) {
        $this->_columns[] = $column;
        return $this;
    }

    public function getColumns() {
        return $this->_columns;
    }

    public function addRow(array $row) {
        return $this->push($row);
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->id,
            'placement' => $this->_placement,
            'caption' => $this->_caption,
            'columns' => $this->_columns,
            'children' => $this->_collection
        ];
    }
}