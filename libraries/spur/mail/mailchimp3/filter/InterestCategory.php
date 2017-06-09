<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp3\filter;

use df;
use df\core;
use df\spur;

class InterestCategory extends Base implements spur\mail\mailchimp3\IInterestCategoryFilter {

    const KEY_NAME = 'categories';

    protected $_type;

    public function setType(?string $type) {
        $this->_type = $type;
        return $this;
    }

    public function getType(): ?string {
        return $this->_type;
    }

    public function toArray(): array {
        $output = parent::toArray();

        if($this->_type !== null) {
            $output['type'] = $this->_type;
        }

        return $output;
    }
}
