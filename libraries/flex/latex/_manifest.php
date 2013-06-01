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
    public function hasClasses();
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

    public function hasClasses() {
        return !empty($this->_classes);
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

interface IContainerNode extends iris\map\INode, core\collection\IQueue, \IteratorAggregate, INodeClassProvider {
    public function getReferenceMap();
}

trait TContainerNode {

    use core\collection\TArrayCollection_Queue;
    use TNodeClassProvider;  

    public function getReferenceMap() {
        $output = array();

        foreach($this->_collection as $child) {
            if($child instanceof flex\latex\IContainerNode) {
                $output = array_merge($output, $child->getReferenceMap());
            }

            if($child instanceof IReferable && ($id = $child->getId())) {
                $output[$id] = $child;
            }
        }

        return $output;
    }  
}

interface IListedNode extends iris\map\INode {
    public function setNumber($number);
    public function getNumber();
}

trait TListedNode {

    protected $_number;

    public function setNumber($number) {
        $this->_number = $number;
        return $this;
    }

    public function getNumber() {
        return $this->_number;
    }
}

interface IPackage extends iris\IProcessor {
    //public function parseCommand($name);
    //public function parseEnvironment($name);
}

interface IActivePackage extends IPackage {
    public function parseCommand($name);
}

interface IEnvironmentNode extends iris\map\IEntity {}


// Map
interface IDocument extends IEnvironmentNode, IContainerNode {
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

    // Bibliography
    public function getBibliography();
}

interface IBlock extends iris\map\IAspect, IContainerNode {}

interface IMacro extends iris\map\IAspect {
    public function setName($name);
    public function getName();
}


interface IReference extends iris\map\IAspect {
    public function setId($id);
    public function getId();
    public function setType($type);
    public function getType();
    public function getTargetType();
}

interface IReferable extends iris\map\IEntity {
    public function setId($id);
    public function getId();
}

trait TReferable {

    public $id;

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getId() {
        return $this->id;
    }
}

interface IGenericBlock extends IBlock, IListedNode, core\IAttributeContainer, IReferable {
    public function isInline($flag=null);
    public function setType($type);
    public function getType();
}

interface IPlacementAware extends iris\map\IAspect {
    public function setPlacement($placement);
    public function getPlacement();
}

trait TPlacementAware {

    protected $_placement;

    public function setPlacement($placement) {
        $this->_placement = $placement;
        return $this;
    }

    public function getPlacement() {
        return $this->_placement;
    }
}



interface IParagraph extends IBlock {}

interface ITextNode extends iris\map\IAspect, INodeClassProvider {
    public function setText($text);
    public function appendText($text);
    public function getText();
    public function isEmpty();
}

interface IMathNode extends IReferable, IListedNode {
    public function isInline($flag=null);
    public function setBlockType($type);
    public function getBlockType();

    public function setSymbols($text);
    public function appendSymbols($text);
    public function getSymbols();
    public function isEmpty();
}



interface ICaptioned extends iris\map\IAspect {
    public function setCaption(IGenericBlock $caption);
    public function getCaption();
}

trait TCaptioned {

    protected $_caption;

    public function setCaption(IGenericBlock $caption) {
        if(!$caption->getType() == 'caption') {
            throw new iris\UnexpectedTokenException(
                'Generic block is not a caption'
            );
        }

        $this->_caption = $caption;
        return $this;
    }

    public function getCaption() {
        return $this->_caption;
    }
}

interface IFigure extends IEnvironmentNode, IContainerNode, IReferable, ICaptioned, IPlacementAware, IListedNode {
    public function setNumber($number);
    public function getNumber();
}

interface ITabular extends iris\map\IAspect {
    public function addColumn(IColumn $column);
    public function getColumns();
}

interface ITable extends ITabular, IEnvironmentNode, IContainerNode, IReferable, ICaptioned, IPlacementAware, IListedNode {
    public function isFirstRowHead();
    public function isFirstColumnHead();
}

interface IColumn extends iris\map\IAspect {
    public function setAlignment($align);
    public function getAlignment();
    public function setParagraphSizing($size);
    public function getParagraphSizing();
    public function hasLeftBorder($flag=null);
    public function hasRightBorder($flag=null);
}

interface IStructure extends IEnvironmentNode, IContainerNode, IReferable, IListedNode {
    public function setType($type);
    public function getType();
}

interface IBibliography extends IEnvironmentNode, IContainerNode {
    public function setDigitLength($length);
    public function getDigitLength();
}