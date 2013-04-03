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
    
class Section extends iris\map\Node implements flex\latex\ISection, core\IDumpable {

    use flex\latex\TContainerNode;

    protected $_number;
    protected $_level = 1;

// Number
    public function setNumber($number) {
        $number = (int)$number;

        if(!$number) {
            $number = null;
        }

        $this->_number = $number;
        return $this;
    }

    public function getNumber() {
        return $this->_number;
    }

// Level
    public function setLevel($level) {
        $this->_level = (int)$level;
        return $this;
    }

    public function getLevel() {
        return $this->_level;
    }


// Dump
    public function getDumpProperties() {
        switch($this->_level) {
            case 1:
                $output = 'section';
                break;

            case 2:
                $output = 'subsection';
                break;

            case 3:
                $output = 'subsubsection';
                break;

            default:
                $output = 'section';
                break;
        }

        if($this->_number !== null) {
            $output = $this->_number.'. '.$output;
        }

        return [
            'type' => $output,
            'children' => $this->_collection
        ];
    }
}