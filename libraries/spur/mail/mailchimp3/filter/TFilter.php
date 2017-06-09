<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp3\filter;

use df;
use df\core;
use df\spur;



// Directional
trait TFilter_Directional {

    protected $_sortField = null;
    protected $_isReversed = false;

    public function setSortField(?string $field) {
        $this->_sortField = $field;
        return $this;
    }

    public function getSortField(): ?string {
        return $this->_sortField;
    }

    public function isReversed(bool $flag=null) {
        if($flag !== null) {
            $this->_isReversed = $flag;
            return $this;
        }

        return $this->_isReversed;
    }

    protected function _applyDirection(array &$output) {
        if($this->_sortField !== null) {
            $output['sort_dir'] = $this->_isReversed ? 'DESC' : 'ASC';
            $output['sort_field'] = $this->_sortField;
        }
    }
}
