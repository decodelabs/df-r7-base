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

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);
        $this->_booId = $reader->getAttribute('booid');

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->setAttribute('booid', $this->_booId);
        $this->_endWriterBlockElement($writer);

        return $this;
    }

    public function render() {
        $string = '<iframe width="100%" height="300" style="background-color:transparent; display:block; max-width: 700px;" frameborder="0" allowtransparency="allowtransparency" scrolling="no" src="//embeds.audioboom.com/boos/'.$this->_booId.'/embed/v4?eid=AQAAANIgxFfWgAoA" title="audioBoom player"></iframe>';

        if(!$this->_isNested) {
            $string = '<div class="audioBooContent">'.$string.'</div>';
        }

        return $this->getView()->html($string);
    }
}