<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\bucket;

class Avatar extends Base
{
    public const USER_SPECIFIC = true;
    public const ALLOW_ONE_PER_USER = true;

    protected $_acceptTypes = [
        'image/*'
    ];

    public function getDisplayName(): string
    {
        return 'User avatar';
    }
}
