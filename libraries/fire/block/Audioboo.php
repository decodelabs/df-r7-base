<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\arch;
use df\aura;
use df\link;

class Audioboo extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = [];

    protected $_booId;

    public function getDisplayName() {
        return 'Audioboom Player';
    }

    public function getFormat() {
        return 'audio';
    }

// Boo id
    public function setBooId($id) {
        if(false !== strpos($id, '://')) {
            $url = new link\http\Url($id);
            $id = $url->getPath()->get(1);
        }

        $this->_booId = $id;
        return $this;
    }

    public function getBooId() {
        return $this->_booId;
    }

// IO
    public function isEmpty() {
        return empty($this->_booId);
    }

    public function readXml(core\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);
        $this->_booId = $reader->getAttribute('booid');

        return $this;
    }

    public function writeXml(core\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->setAttribute('booid', $this->_booId);
        $this->_endWriterBlockElement($writer);

        return $this;
    }

    public function render() {
        $string = '<div class="ab-player block" data-type="Audioboo" data-boourl="//audioboom.com/boos/'.$this->_booId.'/embed"><a href="//audioboom.com/boos/'.$this->_booId.'">listen to this clip on Audioboom</a></div><script type="text/javascript">(function() { var po = document.createElement("script"); po.type = "text/javascript"; po.async = true; po.src = "//d15mj6e6qmt1na.cloudfront.net/assets/embed.js"; var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s); })();</script>';

        if(!$this->_isNested) {
            $string = '<div class="audioBooContent">'.$string.'</div>';
        }

        return $this->getView()->html($string);
    }
}