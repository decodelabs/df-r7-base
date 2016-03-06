<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;

class Debug implements core\ISharedHelper {

    use core\TSharedHelper;

    public function dump(...$args) {
        core\debug()->addDumpList($args, core\debug\StackCall::factory(1), false, false);
        return $this;
    }

    public function dumpDeep(...$args) {
        core\debug()->addDumpList($args, core\debug\StackCall::factory(1), true, false);
        return $this;
    }

    public function dumpNow(...$args) {
        core\debug()->addDumpList($args, core\debug\StackCall::factory(1), false, true)->render();
    }

    public function dumpDeepNow(...$args) {
        core\debug()->addDumpList($args, core\debug\StackCall::factory(1), true, true)->render();
    }

    public function log($log) {
        core\debug()->log($log);
        return $this;
    }

    public function info($info) {
        core\debug()->info($info);
        return $this;
    }

    public function todo($todo) {
        core\debug()->todo($todo);
        return $this;
    }

    public function warning($warning) {
        core\debug()->warning($warning);
        return $this;
    }

    public function error($error) {
        core\debug()->error($error);
        return $this;
    }

    public function exception($exception) {
        core\debug()->exception($exception);
        return $this;
    }

    public function flush() {
        core\debug()->flush();
    }

    public function render() {
        core\debug()->render();
    }
}
