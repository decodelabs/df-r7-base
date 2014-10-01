<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

class TimerBinding extends Binding implements ITimerBinding {
    
    public $duration;

    public function __construct(IDispatcher $dispatcher, $id, $isPersistent, $duration, $callback) {
        parent::__construct($dispatcher, $id, $isPersistent, $callback);

        $this->duration = core\time\Duration::factory($duration);
    }

    public function getType() {
        return 'Timer';
    }

    public function getDuration() {
        return $this->duration;
    }

    public function destroy() {
        $this->dispatcher->unbindTimer($this);
        return $this;
    }

    public function trigger($time) {
        if($this->isFrozen) {
            return $this;
        }
        
        $this->handler->invokeArgs([$this]);

        if(!$this->isPersistent) {
            $this->dispatcher->unbindTimer($this);
        }

        return $this;
    }
}