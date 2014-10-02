<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

class SignalBinding extends Binding implements ISignalBinding {
    
    public $signals = [];

    public function __construct(IDispatcher $dispatcher, $id, $isPersistent, array $signals, $callback) {
        parent::__construct($dispatcher, $id, $isPersistent, $callback);

        $this->eventResource = [];

        foreach($signals as $signal) {
            $signal = halo\process\Signal::factory($signal);
            $this->signals[$signal->getNumber()] = $signal;
            $this->eventResource[$signal->getNumber()] = null;
        }
    }

    public function getType() {
        return 'Signal';
    }

    public function getSignals() {
        return $this->signals;
    }

    public function destroy() {
        $this->dispatcher->removeSignalBinding($this);
        return $this;
    }


    public function trigger($number) {
        if($this->isFrozen) {
            return;
        }

        $this->handler->invokeArgs([$this->signals[$number], $this]);

        if(!$this->isPersistent) {
            $this->dispatcher->removeSignalBinding($this);
        }

        return $this;
    }
}