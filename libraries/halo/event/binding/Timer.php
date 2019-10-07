<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\binding;

use df;
use df\core;
use df\halo;

class Timer extends Base implements halo\event\ITimerBinding
{
    public $duration;

    public function __construct(halo\event\IDispatcher $dispatcher, $id, $isPersistent, $duration, $callback)
    {
        parent::__construct($dispatcher, $id, $isPersistent, $callback);

        $this->duration = core\time\Duration::factory($duration);
    }

    public function getType()
    {
        return 'Timer';
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function destroy()
    {
        $this->dispatcher->removeTimer($this);
        return $this;
    }

    public function trigger($time)
    {
        if ($this->isFrozen) {
            return $this;
        }

        $this->handler->invoke($this);

        if (!$this->isPersistent) {
            $this->dispatcher->removeTimer($this);
        }

        return $this;
    }
}
