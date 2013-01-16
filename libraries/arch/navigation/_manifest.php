<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation;

use df;
use df\core;
use df\arch;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class RecursionException extends RuntimeException {}
class EntryTypeNotFoundException extends RuntimeException {}
class SourceNotFoundException extends RuntimeException {}    



// Interfaces
interface IEntry extends core\IArrayProvider {
    public function getType();
    
    public function setId($id);
    public function getId();
    
    public function setWeight($weight);
    public function getWeight();
}


interface IEntryList extends core\IArrayProvider, \Countable {
    public function addEntries($entries);
    public function addEntry($entry);
    public function getEntry($id);
    public function getEntries();
    public function clearEntries();
}

interface IEntryListGenerator {
    public function generateEntries(IEntryList $entryList);
}


trait TEntryGenerator {

    public function __call($method, $args) {
        $prefix = substr($method, 0, 3);
        if($prefix == 'new' || $prefix == 'add') {
            $output = arch\navigation\entry\Base::factoryArgs(substr($method, 3), $args);

            if($prefix == 'add') {
                $this->addEntry($output);
            }

            return $output;
        }
        
        throw new \BadMethodCallException('Method '.$method.' does not exist');
    }
}

trait TEntryList {

    use TEntryGenerator;

    protected $_entries = array();
    protected $_isSorted = false;

    public static function fromArray(array $entries) {
        return (new self())->addEntries($entries);
    }
    
    public function addEntries($entries) {
        if(!is_array($entries)) {
            $entries = func_get_args();
        }
        
        foreach($entries as $entry) {
            $this->addEntry($entry);
        }
        
        return $this;
    }
    
    public function addEntry($entry) {
        if(!$entry instanceof IEntry) {
            if(is_array($entry)) {
                $entry = arch\navigation\entry\Base::fromArray($entry);
            } else {
                throw new RuntimeException(
                    'Invalid entry definition detected'
                );
            }
        }
        
        if($entry->getWeight() == 0) {
            $entry->setWeight(count($this->_entries) + 1);
        }

        $this->_entries[$entry->getId()] = $entry;
        $this->_isSorted = false;

        return $this;
    }
    
    public function getEntry($id) {
        if(isset($this->_entries[$id])) {
            return $this->_entries[$id];
        }
        
        return null;
    }
    
    public function getEntries() {
        if(!$this->_isSorted) {
            $this->_sortEntries();
            $this->_isSorted = true;
        }

        return $this->_entries;
    }

    protected function _sortEntries() {
        usort($this->_entries, function($a, $b) {
            return $a->getWeight() > $b->getWeight();
        });
    }


    public function clearEntries() {
        $this->_entries = array();
        return $this;
    }


    public function toArray() {
        return $this->getEntries();
    }

    public function count() {
        return count($this->_entries);
    }
}