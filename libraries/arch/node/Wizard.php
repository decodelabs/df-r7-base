<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node;

use DecodeLabs\Exceptional;

use df\core;

abstract class Wizard extends Form
{
    public const DEFAULT_EVENT = 'next';
    public const SECTIONS = [];

    protected function createUi(): void
    {
        $section = $this->getCurrentSection();
        $func = 'create' . ucfirst($section) . 'Ui';

        if (!method_exists($this, $func)) {
            throw Exceptional::Definition(
                'Wizard ui missing for ' . $section . ' section'
            );
        }

        $this->{$func}();
    }

    public function getCurrentSection(): string
    {
        if (empty(static::SECTIONS)) {
            throw Exceptional::Definition(
                'No wizard sections have been defined'
            );
        }

        $section = $this->getStore('section');

        if (!$section) {
            $this->setSection($section = array_values(static::SECTIONS)[0]);
        }

        return $section;
    }

    public function setSection(string $section)
    {
        $this->setStore('section', $section);
        $this->values->clear();

        if ($this->hasStore('data.' . $section)) {
            $this->values->import($this->getStore('data.' . $section));
        } else {
            $func = '_set' . ucfirst($section) . 'DefaultValues';

            if (method_exists($this, $func)) {
                $this->{$func}();
            }
        }

        return $this;
    }



    public function getPrevSection(): ?string
    {
        $current = $this->getCurrentSection();
        $last = null;

        foreach (static::SECTIONS as $section) {
            if ($current == $section) {
                return $last;
            }

            $last = $section;
        }

        return null;
    }

    public function getNextSection(): ?string
    {
        $current = $this->getCurrentSection();
        $found = false;

        foreach (static::SECTIONS as $section) {
            if ($found) {
                return $section;
            } elseif ($current == $section) {
                $found = true;
            }
        }

        return null;
    }

    public function getSectionData(string $section = null): core\collection\ITree
    {
        if ($section === null) {
            $output = [];

            foreach (static::SECTIONS as $section) {
                $output[$section] = $this->getStore('data.' . $section);
            }

            $output = $output;
        } else {
            $output = $this->getStore('data.' . $section);
        }

        return new core\collection\Tree($output);
    }


    // Events
    final protected function onPrevEvent()
    {
        $this->onCurrentEvent();
        $this->setSection((string)$this->getPrevSection());
    }

    final protected function onCurrentEvent()
    {
        $current = $this->getCurrentSection();
        $func = 'on' . ucfirst($current) . 'Submit';

        if (!method_exists($this, $func)) {
            $data = $this->values->toArray();
        } else {
            $data = $this->{$func}();

            if ($data === null) {
                $data = $this->values->toArray();
            }
        }

        if ($data instanceof core\validate\IHandler) {
            $data = $data->getValues();
        } elseif ($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        $this->setStore('data.' . $current, $data);
    }

    final protected function onNextEvent()
    {
        $this->onCurrentEvent();

        if (!$this->isValid()) {
            return;
        }

        if (!$next = $this->getNextSection()) {
            return $this->finalize();
        }

        $this->setSection($next);
    }

    protected function finalize()
    {
        return $this->complete();
    }
}
