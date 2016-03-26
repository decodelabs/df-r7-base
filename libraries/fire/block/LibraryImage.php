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
    protected $_storeDimensions = true;

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

// Dimensions
    public function setWidth($width) {
        $this->_width = (int)$width;

        if(!$this->_width) {
            $this->_width = null;
        }

        return $this;
    }

    public function getWidth() {
        return $this->_width;
    }

    public function setHeight($height) {
        $this->_height = (int)$height;

        if(!$this->_height) {
            $this->_height = null;
        }

        return $this;
    }

    public function getHeight() {
        return $this->_height;
    }

    public function shouldStoreDimensions(bool $flag=null) {
        if($flag !== null) {
            $this->_storeDimensions = $flag;
            return $this;
        }

        return $this->_storeDimensions;
    }


// IO
    public function isEmpty() {
        return empty($this->_imageId);
    }

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        if($reader->hasAttribute('libimage')) {
            $this->_imageId = $reader->getAttribute('libimage');
        } else {
            $this->_imageId = $reader->getAttribute('image');
        }

        if($this->shouldStoreDimensions()) {
            $this->setWidth($reader->getAttribute('width'));
            $this->setHeight($reader->getAttribute('height'));
        }

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);

        $writer->setAttribute('image', $this->_imageId);

        if($this->shouldStoreDimensions()) {
            if($this->_width) {
                $writer->setAttribute('width', $this->_width);
            }

            if($this->_height) {
                $writer->setAttribute('height', $this->_height);
            }
        }

        $this->_endWriterBlockElement($writer);

        return $this;
    }


    public function render() {
        $view = $this->getView();
        $transform = null;

        if($this->_width && $this->_height) {
            $transform = '[cz:'.$this->_width.'|'.$this->_height.']';
        } else if($this->_width || $this->_height) {
            $transform = '[rs:'.$this->_width.'|'.$this->_height.'|f]';
        } else {
            $transform = $this->_getDefaultTransformation();
        }

        $url = $view->media->getImageUrl($this->_imageId, $transform);

        return $view->html->image($url)
            ->addClass('block')
            ->setDataAttribute('type', $this->getName());
    }

    protected function _getDefaultTransformation() {
        return null;
    }
}