<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\arch;
use df\aura;

class Error extends Base {

    protected static $_outputTypes = [];
    protected static $_defaultCategories = [];

    protected $_error;
    protected $_type;
    protected $_data;

    public function getFormat() {
        return 'structure';
    }

    public function isHidden() {
        return true;
    }

    public function setError(\Exception $e=null) {
        $this->_error = $e;
        return $this;
    }

    public function getError() {
        return $this->_error;
    }

    public function setType($type) {
        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function setData($data) {
        $this->_data = $data;
        return $this->_data;
    }

    public function getData() {
        return $this->_data;
    }

    public function getTransitionValue() {
        return $this->_data;
    }

    public function isEmpty() {
        return false;
    }

    public function readXml(core\xml\IReadable $reader) {
        return $this;
    }

    public function writeXml(core\xml\IWritable $writer) {
        throw new RuntimeException(
            'Error block type cannot be saved to xml'
        );
    }

    public function render() {
        $view = $this->getView();

        if(df\Launchpad::$application->isProduction() && !$view->context->request->isArea('admin')) {
            return null;
        }

        $output = $view->html->flashMessage($view->_(
            'Error loading block type: '.$this->_type
        ), 'error');

        if($this->_error) {
            $output->setDescription($this->_error->getMessage());
        }

        return $output;
    }
}