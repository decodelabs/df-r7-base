<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\layout;

use DecodeLabs\Exceptional;
use df\aura;
use df\core;

use df\fire;

class Map implements fire\ILayoutMap
{
    protected $_theme;
    protected $_entries = [];
    protected $_generator;

    public function __construct(aura\theme\ITheme $theme)
    {
        $this->_theme = $theme;
    }

    public function getTheme(): aura\theme\ITheme
    {
        return $this->_theme;
    }

    public function setGenerator(callable $generator = null)
    {
        $this->_generator = $generator;
        return $this;
    }

    public function getGenerator(): ?callable
    {
        return $this->_generator;
    }

    public function setEntries(array $entries)
    {
        $this->_entries = [];
        return $this->addEntries($entries);
    }

    public function addEntries(array $entries)
    {
        foreach ($entries as $entry) {
            if (!$entry instanceof fire\ILayoutMapEntry) {
                throw Exceptional::InvalidArgument(
                    'Invalid map entry detected'
                );
            }

            $this->addEntry($entry);
        }

        return $this;
    }

    public function addEntry(fire\ILayoutMapEntry $entry)
    {
        $this->_entries[$entry->getId()] = $entry;
        return $this;
    }

    public function getEntries()
    {
        return $this->_entries;
    }

    public function removeEntry(string $id)
    {
        unset($this->_entries[$id]);
        return $this;
    }

    public function clearEntries()
    {
        $this->_entries = [];
        return $this;
    }

    public function mapLayout(aura\view\ILayoutView $view)
    {
        if (empty($this->_entries) && $this->_generator) {
            core\lang\Callback::call($this->_generator, $this);
        }

        $request = $view->getContext()->request;

        foreach ($this->_entries as $entry) {
            if (!$entry->allowsTheme($this->_theme)) {
                continue;
            }

            if ($entry->matches($request)) {
                $entry->apply($view);
                break;
            }
        }

        return $this;
    }
}
