<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\time;

use df;
use df\core;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Timer implements core\IStringProvider, Inspectable
{
    use core\TStringProvider;

    protected $_startTime = null;
    protected $_time = 0;

    public function __construct($startTime=true)
    {
        if ($startTime === false || $startTime === null) {
            return;
        }

        $this->start($startTime);
    }

    public function start($startTime=null)
    {
        if ($startTime === null || $startTime === true) {
            $startTime = microtime(true);
        } elseif (is_string($startTime)) {
            $microTime = explode(' ', $startTime);
            $microTime = (float)$microTime[1] + (float)$microTime[0];
        }

        $this->_startTime = (float)$startTime;
        return $this;
    }

    public function stop()
    {
        if ($this->_startTime !== null) {
            $this->_time += microtime(true) - $this->_startTime;
            $this->_startTime = null;
        }

        return $this;
    }

    public function isActive()
    {
        return $this->_startTime !== null;
    }

    public function getTime()
    {
        $output = $this->_time;

        if ($this->_startTime !== null) {
            $output += microtime(true) - $this->_startTime;
        }

        return $output;
    }

    public function getRawTime()
    {
        $this->stop()->start();
        return $this->_time;
    }

    public function toString(): string
    {
        $seconds = $this->getTime();

        if ($seconds > 60) {
            return number_format($seconds / 60, 0).':'.number_format($seconds % 60);
        } elseif ($seconds > 1) {
            return number_format($seconds, 3).' s';
        } elseif ($seconds > 0.0005) {
            return number_format($seconds * 1000, 2).' ms';
        } else {
            return number_format($seconds * 1000, 5).' ms';
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->toString());
    }
}
