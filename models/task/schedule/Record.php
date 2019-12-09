<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\schedule;

use df;
use df\core;
use df\axis;
use df\opal;

class Record extends opal\record\Base
{
    public function canQueue()
    {
        $now = new core\time\Date('now');
        $minute = $now->format('i');

        if ($minute[0] === '0') {
            $minute = substr($minute, 1);
        }

        $hour = $now->format('G');
        $day = $now->format('j');
        $month = $now->format('n');
        $weekday = $now->format('w');

        if (!$this->_match($this['weekday'], $weekday, 0, 6)
        || !$this->_match($this['month'], $month, 1, 12)
        || !$this->_match($this['day'], $day, 1, 31)
        || !$this->_match($this['hour'], $hour, 0, 23)
        || !$this->_match($this['minute'], $minute, 0, 59, true)) {
            return false;
        }

        return true;
    }

    protected function _match($pattern, $value, $low, $high, $checkTime=false)
    {
        $parts = explode(',', $pattern);
        $partCount = count($parts);
        $regular = $partCount > 3;
        $singleNumber = false;

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part == '*') {
                return true;
            }

            if (preg_match('/^\*\/([0-9]+)$/', $part, $matches)) {
                $singleNumber = false;

                if (!$regular) {
                    $regular = (int)$matches[1] < 20;
                }

                if (0 == $value % (int)$matches[1]) {
                    return true;
                } else {
                    continue;
                }
            }

            if (preg_match('/^([0-9]+)\-([0-9]+)$/', $part, $matches)) {
                $singleNumber = false;

                $regular = true;

                if ($value >= (int)$matches[1] && $value <= (int)$matches[2]) {
                    return true;
                } else {
                    continue;
                }
            }

            $singleNumber = $singleNumber !== false ? false : $part;

            if ($value == $part) {
                return true;
            }
        }

        if ($checkTime) {
            if (!$this['lastRun']) {
                return true;
            }

            $duration = $this['lastRun']->timeSince('now')->getMinutes();


            if ($regular && $duration > 10) {
                return true;
            } elseif (!$regular && !$singleNumber && $duration > 10) {
                return true;
            } elseif ($singleNumber && $duration > 60 && $value > $singleNumber) {
                return true;
            }
        }

        return false;
    }
}
