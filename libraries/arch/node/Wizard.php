<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\aura;

abstract class Wizard extends Form {

    const DEFAULT_EVENT = 'next';
    const SECTIONS = [];

    protected function createUi() {
        $section = $this->getCurrentSection();
        $func = 'create'.ucfirst($section).'Ui';

        if(!method_exists($this, $func)) {
            throw core\Error::EDefinition(
                'Wizard ui missing for '.$section.' section'
            );
        }

        $this->{$func}();
    }

    public function getCurrentSection() {
        if(empty(static::SECTIONS)) {
            throw core\Error::EDefinition(
                'No wizard sections have been defined'
            );
        }

        $section = $this->getStore('section');

        if(!$section) {
            $this->setSection(array_values(static::SECTIONS)[0]);
        }

        return $section;
    }

    public function setSection($section) {
        $this->setStore('section', $section);
        $this->values->clear();

        if($this->hasStore('data.'.$section)) {
            $this->values->import($this->getStore('data.'.$section));
        } else {
            $func = '_set'.ucfirst($section).'DefaultValues';

            if(method_exists($this, $func)) {
                $this->{$func}();
            }
        }
    }



    public function getPrevSection() {
        $current = $this->getCurrentSection();
        $last = null;

        foreach(static::SECTIONS as $section) {
            if($current == $section) {
                return $last;
            }

            $last = $section;
        }

        return null;
    }

    public function getNextSection() {
        $current = $this->getCurrentSection();
        $found = false;

        foreach(static::SECTIONS as $section) {
            if($found) {
                return $section;
            } else if($current == $section) {
                $found = true;
            }
        }

        return null;
    }

    public function getSectionData($section=null) {
        if($section === null) {
            $output = [];

            foreach(static::SECTIONS as $section) {
                $output[$section] = $this->getStore('data.'.$section);
            }

            $output = $output;
        } else {
            $output = $this->getStore('data.'.$section);
        }

        return new core\collection\Tree($output);
    }


// Events
    final protected function onPrevEvent() {
        $this->onCurrentEvent();
        $this->setSection($this->getPrevSection());
    }

    final protected function onCurrentEvent() {
        $current = $this->getCurrentSection();
        $func = 'on'.ucfirst($current).'Submit';

        if(!method_exists($this, $func)) {
            $data = $this->values->toArray();
        } else {
            $data = $this->{$func}();

            if($data === null) {
                $data = $this->values->toArray();
            }
        }

        if($data instanceof core\validate\IHandler) {
            $data = $data->getValues();
        } else if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        $this->setStore('data.'.$current, $data);
    }

    final protected function onNextEvent() {
        $this->onCurrentEvent();

        if(!$this->isValid()) {
            return;
        }

        if(!$next = $this->getNextSection()) {
            return $this->finalize();
        }

        $this->setSection($next);
    }

    protected function finalize() {
        return $this->complete();
    }
}