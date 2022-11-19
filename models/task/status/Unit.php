<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\status;

use df\axis;

class Unit extends axis\unit\Enum
{
    public const PENDING = 'Pending';
    public const LOCKED = 'Locked';
    public const PROCESSING = 'Processing';
    public const LAGGING = 'Lagging';
    public const COMPLETE = 'Complete';
}
