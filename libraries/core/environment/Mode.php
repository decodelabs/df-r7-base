<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\environment;

use df\core;

class Mode extends core\lang\Enum
{
    public const DEVELOPMENT = 'Development';
    public const TESTING = 'Testing';
    public const PRODUCTION = 'Production';
}
