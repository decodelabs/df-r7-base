<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\daemon;

use df;
use df\core;
use df\halo;
use df\link;
    
class Angel extends Base implements IAngel {

    const REQUIRES_PRIVILEGED_PROCESS = false;
    const FORK_ON_LOAD = false; // delete me

    public static function factory($name=null) {
        return new self();
    }

    protected function _preparePrivilegedResources() {
        $left = link\socket\Client::factory('tcp');
        $right = $left->connectPair();
        core\dump($left, $right);
    }

    protected function _setup() {

    }
}