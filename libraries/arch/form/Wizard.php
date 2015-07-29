<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;
use df\aura;

abstract class Wizard extends Action {
    
    const DEFAULT_EVENT = 'next';

    protected $_sections = [];

    protected function _createUi() {
        $section = $this->_getCurrentSection();
        $func = '_create'.ucfirst($section).'Ui';

        if(!method_exists($this, $func)) {
            throw new LogicException(
                'Wizard ui missing for '.$section.' section'
            );
        }

        $this->{$func}();
    }

    protected function _getCurrentSection() {
        if(empty($this->_sections)) {
            throw new LogicException(
                'No wizard sections have been defined'
            );
        }

        $section = $this->getStore('section');

        if(!$section) {
            reset($this->_sections);
            $section = current($this->_sections);
            $this->_setSection($section);
        }

        return $section;
    }

    protected function _setSection($section) {
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

    protected function _getSectionData($section=null) {
        if($section === null) {
            $output = [];

            foreach($this->_sections as $section) {
                $output[$section] = $this->getStore('data.'.$section);
            }

            $output = $output;
        } else {
            $output = $this->getStore('data.'.$section);
        }

        return new core\collection\Tree($output);
    }

    protected function _getPrevSection() {
        $current = $this->_getCurrentSection();
        $last = null;

        foreach($this->_sections as $section) {
            if($current == $section) {
                return $last;
            }

            $last = $section;
        }

        return null;
    }

    protected function _getNextSection() {
        $current = $this->_getCurrentSection();
        $found = false;

        foreach($this->_sections as $section) {
            if($found) {
                return $section;
            } else if($current == $section) {
                $found = true;
            }
        }

        return null;
    }


// Events
    final protected function _onPrevEvent() {
        $this->_onCurrentEvent();
        $this->_setSection($this->_getPrevSection());
    }

    final protected function _onCurrentEvent() {
        $current = $this->_getCurrentSection();
        $func = '_on'.ucfirst($current).'Submit';

        if(!method_exists($this, $func)) {
            $data = $this->values->toArray();
        } else {
            $data = $this->{$func}();
        }

        if($data instanceof core\validate\IHandler) {
            $data = $data->getValues();
        } else if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        $this->setStore('data.'.$current, $data);
    }

    final protected function _onNextEvent() {
        $this->_onCurrentEvent();

        if(!$this->isValid()) {
            return;
        }

        if(!$next = $this->_getNextSection()) {
            return $this->_onFinalizeEvent();
        }

        $this->_setSection($next);
    }

    protected function _onFinalizeEvent() {
        return $this->complete();
    }
}