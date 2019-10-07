<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\binding;

use df;
use df\core;
use df\halo;

use DecodeLabs\Systemic;

class Signal extends Base implements halo\event\ISignalBinding
{
    public $signals = [];

    public function __construct(halo\event\IDispatcher $dispatcher, $id, $isPersistent, array $signals, $callback)
    {
        parent::__construct($dispatcher, $id, $isPersistent, $callback);

        $this->eventResource = [];

        foreach ($signals as $signal) {
            $signal = Systemic::$process->newSignal($signal);
            $this->signals[$signal->getNumber()] = $signal;
            $this->eventResource[$signal->getNumber()] = null;
        }
    }

    public function getType()
    {
        return 'Signal';
    }

    public function getSignals()
    {
        return $this->signals;
    }

    public function destroy()
    {
        $this->dispatcher->removeSignalBinding($this);
        return $this;
    }


    public function trigger($number)
    {
        if ($this->isFrozen) {
            return;
        }

        $this->handler->invoke($this->signals[$number], $this);

        if (!$this->isPersistent) {
            $this->dispatcher->removeSignalBinding($this);
        }

        return $this;
    }
}
