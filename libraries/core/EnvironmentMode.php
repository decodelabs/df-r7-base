<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

class EnvironmentMode extends core\lang\Enum {

    const DEVELOPMENT = 'Development';
    const TESTING = 'Testing';
    const PRODUCTION = 'Production';
}