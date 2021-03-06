<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\bucket;

use df;
use df\core;
use df\neon;

class Avatar extends Base {

    const USER_SPECIFIC = true;
    const ALLOW_ONE_PER_USER = true;

    protected $_acceptTypes = [
        'image/*'
    ];

    public function getDisplayName(): string {
        return 'User avatar';
    }
}
