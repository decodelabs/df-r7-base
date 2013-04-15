<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component\template;

use df;
use df\core;
use df\arch;
use df\aura;
    
abstract class CollectionList extends arch\component\Base implements aura\html\widget\IWidgetProxy {

    protected $_collection;
    protected $_errorMessage;
    protected $_fields = array();
    protected $_urlRedirect = true;

    protected function _init($collection=null, array $fields=null) {
        if($collection) {
            $this->setCollection($collection);
        }

        if(!empty($fields)) {
            $this->setFields($fields);
        }
    }

// Collection
    public function setCollection($collection) {
        $this->_collection = $collection;
        return $this;
    }

    public function getCollection() {
        return $this->_collection;
    }

// Error
    public function setErrorMessage($message) {
        $this->_errorMessage = $message;
        return $this;
    }

    public function getErrorMessage() {
        return $this->_errorMessage;
    }


// Fields
    public function setFields(array $fields) {
        foreach($fields as $key => $value) {
            if($value === true && isset($this->_fields[$key]) && is_callable($this->_fields[$key])) {
                continue;
            }

            if(!is_callable($value)) {
                $value = (bool)$value;
            }

            $this->_fields[$key] = $value;
        }

        return $this;
    }

    public function getFields() {
        return $this->_fields;
    }

    public function hide($keys) {
        if(!is_array($keys)) {
            $keys = func_get_args();
        }

        foreach($keys as $key) {
            if(isset($this->_fields[$key])) {
                $this->_fields[$key] = false;
            }
        }

        return $this;
    }

    public function showField($keys) {
        if(!is_array($keys)) {
            $keys = func_get_args();
        }

        foreach($keys as $key) {
            if(isset($this->_fields[$key]) && $this->_fields[$key] == false) {
                $this->_fields[$key] = true;
            }
        }

        return $this;
    }

    public function addCustomField($key, Callable $callback) {
        $this->_fields[$key] = $callback;
        return $this;
    }

// Url redirect
    public function setUrlRedirect($redirect) {
        $this->_urlRedirect = $redirect;
        return $this;
    }

    public function getUrlRedirect() {
        return $this->_urlRedirect;
    }


// Render
    public function toWidget() {
        return $this->render();
    }

    protected function _execute() {
        $output = $this->view->html->collectionList($this->_collection);

        if($this->_errorMessage !== null) {
            $output->setErrorMessage($this->_errorMessage);
        } else {
            $output->setErrorMessage($this->_('This list is currently empty'));
        }

        foreach($this->_fields as $key => $value) {
            if($value === true) {
                $func = 'add'.ucfirst($key).'Field';

                if(!method_exists($this, $func)) {
                    throw new arch\RuntimeException(
                        'Collection list component does not have a handler for field '.$key
                    );
                }

                $this->{$func}($output);
            } else if(is_callable($value)) {
                $value($output);
            }
        }

        return $output;
    }
}