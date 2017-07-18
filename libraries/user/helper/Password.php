<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df;
use df\core;
use df\user;
use df\flex;

class Password extends Base {

    public function analyze($password) {
        return new flex\PasswordAnalyzer($password, df\Launchpad::$app->getPassKey());
    }

    public function generate() {
        return flex\Generator::random(8, 14, '!#*.');
    }

    public function hash($message) {
        return core\crypt\Util::passwordHash($message, df\Launchpad::$app->getPassKey());
    }

    public function hexHash($message) {
        return bin2hex($this->hash($message));
    }
}
