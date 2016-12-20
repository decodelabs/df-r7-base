<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\flex;
use df\aura;

class LibraryImage extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = ['Description'];

    protected $_imageId;
    protected $_width;
    protected $_height;
    protected $_link;

    public function getFormat() {
        return 'image';
    }

// Image
    public function setImageId($id) {
        $this->_imageId = $id;
        return $this;
    }

    public function getImageId() {
        return $this->_imageId;
    }


// Link
    public function setLink(string $link=null) {
        $this->_link = $link;
        return $this;
    }

    public function getLink() {
        return $this->_link;
    }


// IO
    public function isEmpty() {
        return empty($this->_imageId);
    }

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        $this->_imageId = $reader->getAttribute('image');
        $this->setLink($reader->getAttribute('href'));

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);

        $writer->setAttribute('image', $this->_imageId);

        if($this->_link) {
            $writer->setAttribute('href', $this->_link);
        }

        $this->_endWriterBlockElement($writer);

        return $this;
    }


    public function render() {
        $view = $this->getView();

        $url = $view->media->getImageUrl($this->_imageId);
        $output = $view->html->image($url);

        if($this->_link) {
            $output = $view->html->link($this->_link, $output);
        }

        $output
            ->addClass('block')
            ->setDataAttribute('type', $this->getName());

        return $output;
    }
}