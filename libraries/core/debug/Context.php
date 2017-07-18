<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/debug/_manifest');
df\Launchpad::loadBaseClass('core/debug/StackCall');
df\Launchpad::loadBaseClass('core/debug/StackTrace');
df\Launchpad::loadBaseClass('core/log/_manifest');
df\Launchpad::loadBaseClass('core/log/node/Group');

class Context extends core\log\node\Group implements IContext {

    use core\log\TWriterProvider;

    public $runningTime;
    protected $_stackTrace;

    public function __construct() {
        $this->setNodeTitle('Context');
    }

// IO
    public function render() {
        while(ob_get_level()) {
            ob_end_clean();
        }

        if(df\Launchpad::$app) {
            $this->runningTime = df\Launchpad::$app->getRunningTime();
        } else {
            $this->runningTime = 0;
        }

        $this->_stackTrace = StackTrace::factory(1);
        $this->_stackTrace->stripDebugEntries();

        if(df\Launchpad::$runner) {
            df\Launchpad::$runner->renderDebugContext($this);
        } else {
            df\Launchpad::loadBaseClass('core/debug/renderer/PlainText');
            echo (new core\debug\renderer\PlainText($this))->render();
        }

        df\Launchpad::shutdown();
    }

    public function execute() {
        if(df\Launchpad::$app
        && df\Launchpad::$app->isDevelopment()
        && $this->isCritical()) {
            return $this->render();
        }

        $this->flush();

        return $this;
    }

    public function flush() {
        foreach($this->getWriters() as $writer) {
            $writer->writeNode($this, $this);
            $writer->flush($this);
        }

        return $this;
    }

    public function toString(): string {
        $renderer = new core\debug\renderer\PlainText($this);
        return $renderer->render();
    }

    public function getStackTrace() {
        return $this->_stackTrace;
    }

    public function getNodeType() {
        return 'context';
    }
}
