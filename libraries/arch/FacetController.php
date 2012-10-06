<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\aura;
    
class FacetController implements IFacetController {

    use TContextAware;
    use core\TArrayAccessedAttributeContainer;

    protected $_initializer;
    protected $_action;
    protected $_facets = array();

    public function __construct(IContext $context, Callable $initializer=null) {
        $this->_context = $context;
        $this->setInitializer($initializer);
    }

    public function setInitializer(Callable $initializer=null) {
        $this->_initializer = $initializer;
        return $this;
    }

    public function getInitializer() {
        return $this->_initializer;
    }


    public function setAction(Callable $action) {
        $this->_action = $action;
        return $this;
    }

    public function getAction() {
        return $this->_action;
    }


    public function addFacet($id, Callable $action) {
        $this->_facets[$id] = $action;
        return $this;
    }

    public function hasFacet($id) {
        return isset($this->_facets[$id]);
    }

    public function getFacet($id) {
        if($this->hasFacet($id)) {
            return $this->_facets[$id];
        }
    }

    public function removeFacet($id) {
        unset($this->_facets[$id]);
        return $this;
    }



    public function __get($id) {
        $value = null;

        if($facet = $this->getFacet($id)) {
            $value = call_user_func_array($facet, [$this]);
        }

        return new aura\view\content\GenericRenderer($value);
    }


// Response
    public function toResponse() {
        if($this->_initializer) {
            call_user_func_array($this->_initializer, [$this]);
        }

        if(!$this->_action) {
            throw new RuntimeException(
                'No main action has been defined for facet controller at '.$this->_context->getRequest()
            );
        }

        $output = call_user_func_array($this->_action, [$this]);
        
        return $output;
    }
}