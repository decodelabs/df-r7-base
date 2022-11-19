<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\helper;

use DecodeLabs\R7\Legacy;
use df\core;

use df\flex;

class Password extends Base
{
    public function analyze($password)
    {
        return new flex\PasswordAnalyzer($password, Legacy::getPassKey());
    }

    public function generate()
    {
        return flex\Generator::random(8, 14, '!#*.');
    }

    public function hash($message)
    {
        return core\crypt\Util::passwordHash($message, Legacy::getPassKey());
    }

    public function hexHash($message)
    {
        return bin2hex($this->hash($message));
    }
}
