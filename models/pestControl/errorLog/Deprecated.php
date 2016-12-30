<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\errorLog;

use df;
use df\core;
use df\apex;
use df\axis;

class Deprecated extends \Exception {

    public function __construct($message) {
        parent::__construct('DEPRECATED: '.$message);
    }
}