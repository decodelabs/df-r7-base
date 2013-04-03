<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex;

use df;
use df\core;
use df\flex;
use df\iris;
    
// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface INodeClassProvider {
    public function setClasses($classes);
    public function addClasses($classes);
    public function addClass($class);
    public function getClasses();
    public function removeClass($class);
    public function clearClasses();
}


trait TNodeClassProvider {

    protected $_classes = array();

    public function setClasses($classes) {
        if(!is_array($classes)) {
            $classes = func_get_args();
        }

        $this->_classes = array();
        return $this->addClasses($classes);
    }

    public function addClasses($classes) {
        if(!is_array($classes)) {
            $classes = func_get_args();
        }

        foreach($classes as $class) {
            $this->addClass($class);
        }

        return $this;
    }

    public function addClass($class) {
        $this->_classes[$class] = true;
        return $this;
    }

    public function getClasses() {
        return array_keys($this->_classes);
    }

    public function removeClass($class) {
        unset($this->_classes[$class]);
        return $this;
    }

    public function clearClasses() {
        $this->_classes = array();
        return $this;
    }
}

interface IContainerNode extends iris\map\INode, core\collection\IQueue, INodeClassProvider {}

trait TContainerNode {

    use core\collection\TArrayCollection_Queue;
    use TNodeClassProvider;    
}

interface IPackage extends iris\IProcessor {
    public function parseCommand($name);
    public function parseEnvironment($name);
}



// Map
interface IDocument extends iris\map\IEntity, IContainerNode {
    // Class
    public function setDocumentClass($class);
    public function getDocumentClass();

    // Options
    public function setOptions(array $options);
    public function addOptions(array $options);
    public function addOption($option);
    public function getOptions();
    public function clearOptions();

    // Packages
    public function addPackage($name, array $options=array());
    public function hasPackage($name);
    public function getPackages();

    // Top matter
    public function setTitle($title);
    public function getTitle();
    public function setAuthor($author);
    public function getAuthor();
    public function setDate($date);
    public function getDate();
}

interface ISection extends iris\map\IAspect, IContainerNode {
    public function setNumber($number);
    public function getNumber();
    public function setLevel($level);
    public function getLevel();
}


interface IParagraph extends iris\map\IAspect, IContainerNode {}

interface ITextNode extends iris\map\IAspect, INodeClassProvider {
    public function setText($text);
    public function appendText($text);
    public function getText();
    public function isEmpty();
}

interface IMathNode extends iris\map\IAspect {
    public function isInline($flag=null);
    public function setSymbols($text);
    public function appendSymbols($text);
    public function getSymbols();
    public function isEmpty();
}