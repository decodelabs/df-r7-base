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


interface IEntryList extends core\IArrayProvider {
    public function addEntries($entries);
    public function addEntry($entry);
    public function getEntries();
}