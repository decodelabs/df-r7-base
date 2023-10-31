<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\helper;

use DecodeLabs\R7\Legacy;
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
}
