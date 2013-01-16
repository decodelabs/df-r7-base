<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;
    
class Definitions implements IDefinitionsContainer, core\IDumpable {

    use TStructure_Container;
    use TStructure_MetaData;
    use TAttributeModule;
    use TAttributeModule_Structure;

    public function __construct(array $defs=null) {
        if($defs) {
            $this->setDefinitions($defs);
        }
    }

    public function getElementName() {
        return 'defs';
    }

    public function getDefinitionsElement() {
        return $this;
    }

    public function setDefinitions(array $defs) {
        $this->_children = array();
        return $this->addDefinitions($defs);
    }

    public function addDefinitions(array $defs) {
        foreach($defs as $def) {
            if(!$def instanceof IElement) {
                throw new InvalidArgumentException(
                    'Invalid definition element detected'
                );
            }

            $this->addDefinition($def);
        }

        return $this;
    }

    public function addDefinition(IElement $element) {
        $this->_children[] = $element;
        return $this;
    }

    public function getDefinitions() {
        return $this->_children;
    }

    public function removeDefinition(IElement $element) {
        foreach($this->_children as $i => $def) {
            if($def === $element) {
                unset($this->_children[$id]);
                break;
            }
        }

        return $this;
    }

    public function clearDefinitions() {
        $this->_children = array();
        return $this;
    }
}