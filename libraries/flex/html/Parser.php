<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\html;

use df;
use df\core;
use df\flex;
    
class Parser implements flex\ITextProducer {

    use flex\TParser;

    public function toText() {
        $html = str_replace("\r\n", "\n", $this->source);
        $html = str_replace("\r", "\n", $html);
        $doc = new \DOMDocument();

        if(!$doc->loadHTML($html)) {
            throw new flex\UnexpectedValueException(
                'DOMDOcument could not load html'
            );
        }

        $output = $this->_iterateNode($doc);
        $output = preg_replace("/[ \t]*\n[ \t]*/im", "\n", trim($output));

        return $output;
    }

    protected function _iterateNode($node) {
        if($node instanceof \DOMText) {
            return preg_replace("/\\s+/im", ' ', $node->wholeText);
        } else if($node instanceof \DOMDocumentType) {
            return '';
        }

        $nextName = $this->_getNextChildName($node);
        $prevName = $this->_getPrevChildName($node);
        $name = $this->_getNodeName($node);

        switch($name) {
            case 'hr':
                return '------'."\n";

            case 'style':
            case 'head':
            case 'title':
            case 'meta':
            case 'script':
                return '';

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'p':
            case 'div':
            case 'section':
            case 'article':
                $output = "\n";
                break;

            default:
                $output = '';
                break;
        }

        for($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);
            $output .= $this->_iterateNode($child);
        }

        switch($name) {
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $output .= "\n";
                break;

            case 'p':
            case 'br':
                if($nextName != 'div') {
                    $output .= "\n";
                }

                break;

            case 'div':
                if($nextName != 'div' && $nextName !== null) {
                    $output .= "\n";
                }

                break;

            case 'a':
                $href = $node->getAttribute('href');

                if($href === null) {
                    if($node->getAttribute('name') !== null) {
                        $output = '['.$output.']';
                    }
                } else {
                    if($href != $output) {
                        $output = '['.$output.']('.$href.')';
                    }
                }

                if(preg_match('/^h[1-6]$/', $nextName)) {
                    $output .= "\n";
                }

                break;

            default:
                break;
        }

        return $output;
    }

    protected function _getNextChildName($node) {
        $nextNode = $node->nextSibling;

        while($nextNode !== null) {
            if($nextNode instanceof \DOMElement) {
                break;
            }

            $nextNode = $nextNode->nextSibling;
        }

        return $this->_getNodeName($nextNode);
    }

    protected function _getPrevChildName($node) {
        $prevNode = $node->previousSibling;

        while($prevNode !== null) {
            if($prevNode instanceof \DOMElement) {
                break;
            }

            $prevNode = $prevNode->previousSibling;
        }

        return $this->_getNodeName($prevNode);
    }

    protected function _getNodeName($node) {
        $name = null;

        if($node instanceof \DOMElement && $node !== null) {
            $name = strtolower($node->nodeName);
        }

        return $name;
    }
}