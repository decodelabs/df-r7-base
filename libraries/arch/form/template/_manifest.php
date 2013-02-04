<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\template;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;
    

interface IInlineFieldRenderableDelegate {
    public function renderFieldArea($label=null);
    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea);
}

trait TInlineFieldRenderableDelegate {

    public function renderFieldArea($label=null) {
        $this->renderFieldAreaContent(
            $output = $this->html->fieldArea($label)
        );

        return $output;
    }
}

interface ISelfContainedRenderableDelegate {
    public function renderFieldSet($legend=null);
    public function renderFieldSetContent(aura\html\widget\FieldSet $fieldSet);
}

trait TSelfContainedRenderableDelegate {

    public function renderFieldSet($legend=null) {
        $this->renderFieldSet(
            $output = $this->html->fieldSet($legend)
        );

        return $output;
    }
}


interface ISelectorDelegate {
    public function isRequired($flag=null);
    public function isForOne($flag=null);
    public function isForMany($flag=null);

    public function isSelected($id);
    public function setSelected($selected);
    public function getSelected();
    public function hasSelection();

    public function apply();
}

trait TSelectorDelegate {

    protected $_isRequired = false;
    protected $_isForMany = true;

    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }

        return $this->_isRequired;
    }

    public function isForOne($flag=null) {
        if($flag !== null) {
            $this->_isForMany = !(bool)$flag;
            return $this;
        }

        return !$this->_isForMany;
    }

    public function isForMany($flag=null) {
        if($flag !== null) {
            $this->_isForMany = (bool)$flag;
            return $this;
        }

        return $this->_isForMany;
    }
}

trait TSelectorDelegateQueryTools {

    protected function _normalizeQueryResult($result) {
        if($result instanceof opal\query\IQuery) {
            $result = $result->toArray();
        }

        if(!$result instanceof \Iterator
        && !$result instanceof core\collection\ICollection
        && !is_array($result)) {
            $result = array();
        }

        return $result;
    }

    protected function _extractQueryResult($result) {
        $result = $this->_normalizeQueryResult($result);

        foreach($result as $entry) {
            return $entry;
        }
    }

    protected function _isQueryResultEmpty($result) {
        if($result instanceof core\collection\ICollection) {
            return $result->isEmpty();
        } else if(is_array($result)) {
            return empty($result);
        } else {
            return true;
        }
    }

    protected function _getResultId($result) {
        return $result['id'];
    }

    protected function _getResultDisplayName($result) {
        return $result['name'];
    }
}