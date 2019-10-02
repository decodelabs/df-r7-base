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

class AnnualRange implements IAnnualRange, Inspectable
{
    protected $_start = null;
    protected $_end = null;
    protected $_isOpen = null;
    protected $_year = null;

    public function __construct($start, $end, $timezone=null)
    {
        $this->update($start, $end, $timezone);
    }

    public function __clone()
    {
        $this->_start = clone $this->_start;
        $this->_end = clone $this->_end;
    }

    public function update($start, $end, $timezone=null)
    {
        $this->_start = $this->_end = $this->_isOpen = $this->_year = null;
        $time = true;

        if ($timezone === false) {
            $timezone = null;
            $time = false;
        }

        $this->_start = Date::factory($start, $timezone, $time);
        $this->_end = Date::factory($end, $timezone, $time);

        if ($time && $timezone !== null && $timezone !== false) {
            if ($timezone === true) {
                $this->_start->toUserTimezone();
                $this->_end->toUserTimezone();
            } else {
                $this->_start->toTimezone($timezone);
                $this->_end->toTimezone($timezone);
            }
        }

        if ($this->_start->gt($this->_end)) {
            $this->_start->modify('-1 year');
        }

        if ($this->_start->isFuture()) {
            $this->_start->modify('-1 year');
            $this->_end->modify('-1 year');
        }

        if ($this->_start->lt('-1 year')) {
            $this->_start->modify('+1 year');
            $this->_end->modify('+1 year');
        }

        return $this;
    }


    // Dates
    public function getStartDate(): IDate
    {
        return $this->_start;
    }

    public function getNextStartDate(): IDate
    {
        return $this->_start->modifyNew('+1 year');
    }

    public function getEndDate(): IDate
    {
        return $this->_end;
    }


    // Years
    public function getStartYear(): int
    {
        return (int)$this->_start->format('Y');
    }

    public function getEndYear(): int
    {
        return (int)$this->_end->format('Y');
    }

    public function getActiveYear(): int
    {
        if ($this->_year === null) {
            $this->_year = (int)$this->_start->format('Y');
            $test = $this->_start->modifyNew('1 january +1 year');

            if ($test->timeSince($this->_start)->lt('6 months')) {
                $this->_year++;
            }
        }

        return $this->_year;
    }



    // Open
    public function isOpen(): bool
    {
        if ($this->_isOpen === null) {
            $this->_isOpen = $this->_start->lte('now') && $this->_end->gte('now');
        }

        return $this->_isOpen;
    }

    public function getTimeUntilStart(): ?IDuration
    {
        if ($this->isOpen()) {
            return null;
        }

        return $this->_start->timeSince('now');
    }

    public function getTimeUntilEnd(): ?IDuration
    {
        if (!$this->isOpen()) {
            return null;
        }

        return $this->_end->timeSince('now');
    }



    // Prev / next
    public function getPrevious(): IAnnualRange
    {
        $output = clone $this;

        $output->_start->modify('-1 year');
        $output->_end->modify('-1 year');
        $output->_isOpen = $this->_year = null;

        return $output;
    }

    public function getNext(): IAnnualRange
    {
        $output = clone $this;

        $output->_start->modify('+1 year');
        $output->_end->modify('+1 year');
        $output->_isOpen = $this->_year = null;

        return $output;
    }



    // Events
    public function getEventDate($date, $timezone=null): IDate
    {
        if ($timezone === false) {
            $timezone = null;
        }

        $date = Date::factory($date, $timezone, $hasTime = $this->_start->hasTime());

        if ($hasTime) {
            $date->toTimezone($this->_start->getTimezone());
        }

        if ($date->lt('-2 weeks')) {
            $date->modify('+1 year');
        }

        if ($date->gt($this->_end->modifyNew('+1 year'))) {
            $date->modify('-1 year');
        }

        return $date;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*start' => $inspector($this->_start),
                '*end' => $inspector($this->_end),
                '*next' => $inspector($this->getNextStartDate()),
                '*year' => $inspector($this->getActiveYear()),
                '*open' => $inspector($this->isOpen())
            ]);
    }
}
