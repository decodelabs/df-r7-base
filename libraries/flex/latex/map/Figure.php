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

class Figure extends iris\map\Node implements flex\latex\IFigure, core\IDumpable {

    use flex\latex\TContainerNode;
    use flex\latex\TReferable;
    use flex\latex\TCaptioned;
    use flex\latex\TPlacementAware;
    use flex\latex\TListedNode;

    public $number;

    public function setNumber($number) {
        $this->number = $number;
        return $this;
    }

    public function getNumber() {
        return $this->number;
    }

    public function isEmpty(): bool {
        return false;
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'placement' => $this->_placement,
            'caption' => $this->_caption,
            'children' => $this->_collection
        ];
    }
}
