<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\filter;

use df;
use df\core;
use df\spur;
use df\mint;

class Plan extends Base implements spur\payment\stripe2\IPlanFilter {

    use TFilter_Created;

    public function toArray(): array {
        $output = parent::toArray();
        $this->_applyCreated($output);

        return $output;
    }
}