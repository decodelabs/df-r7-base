<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\status;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\Enum
{
    const PENDING = 'Pending';
    const LOCKED = 'Locked';
    const PROCESSING = 'Processing';
    const LAGGING = 'Lagging';
    const COMPLETE = 'Complete';
}
