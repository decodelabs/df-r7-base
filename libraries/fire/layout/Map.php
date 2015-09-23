<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\layout;

use df;
use df\core;
use df\fire;
use df\arch;
use df\aura;
    
class Map implements fire\layout\IMap {

    protected $_theme;
    protected $_entries = [];
    protected $_generator;

    public function __construct(aura\theme\ITheme $theme) {
        $this->_theme = $theme;
    }

    public function getTheme() {
        return $this->_theme;
    }

    public function setGenerator($generator=null) {
        if($generator !== null) {
            $generator = core\lang\Callback::factory($generator);
        }

        $this->_generator = $generator;
        return $this;
    }

    public function getGenerator() {
        return $this->_generator;
    }

    public function setEntries(array $entries) {
        $this->_entries = [];
        return $this->addEntries($entries);
    }

    public function addEntries(array $entries) {
        foreach($entries as $entry) {
            if(!$entry instanceof fire\layout\IMapEntry) {
                throw new fire\layout\InvalidArgumentException(
                    'Invalid map entry detected'
                );
            }

            $this->addEntry($entry);
        }

        return $this;
    }

    public function addEntry(fire\layout\IMapEntry $entry) {
        $this->_entries[$entry->getId()] = $entry;
        return $this;
    }

    public function getEntries() {
        return $this->_entries;
    }

    public function removeEntry($id) {
        if($id instanceof fire\layout\IMapEntry) {
            $id = $id->getId();
        }

        unset($this->_entries[$id]);
        return $this;
    }

    public function clearEntries() {
        $this->_entries = [];
        return $this;
    }

    public function mapLayout(aura\view\ILayoutView $view) {
        if(empty($this->_entries) && $this->_generator) {
            $this->_generator->invoke($this);
        }

        $request = $view->getContext()->request;

        foreach($this->_entries as $entry) {
            if(!$entry->allowsTheme($this->_theme)) {
                continue;
            }

            if($entry->matches($request)) {
                $entry->apply($view);
                break;
            }
        }

        return $this;
    }
}