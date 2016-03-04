<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\aura;

class Element extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = [];

    protected $_slug;

    public function getFormat() {
        return 'structure';
    }

    public function setSlug($slug) {
        $this->_slug = $slug;
        return $this;
    }

    public function getSlug() {
        return $this->_slug;
    }


    public function isEmpty() {
        return !strlen($this->_slug);
    }

    public function readXml(core\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);
        $this->_slug = $reader->getAttribute('slug');

        return $this;
    }

    public function writeXml(core\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->setAttribute('slug', $this->_slug);
        $this->_endWriterBlockElement($writer);

        return $this;
    }


    public function render() {
        $view = $this->getView();
        return $view->nightfire->renderElement($this->_slug);
    }
}