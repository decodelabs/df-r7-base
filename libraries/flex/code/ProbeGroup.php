<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\code;

use df;
use df\core;
use df\flex;

class ProbeGroup implements IProbeGroup {

    use core\collection\TArrayCollection_Map;

    public function __construct(array $probes=[]) {
        $this->_collection = $probes;
    }

    public function getAll() {
        $output = null;

        foreach($this->_collection as $location => $probe) {
            if($output === null) {
                $output = clone $probe;
            } else {
                $probe->exportTo($output);
            }
        }

        return $output;
    }
}