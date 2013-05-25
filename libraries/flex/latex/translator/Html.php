<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\translator;

use df;
use df\core;
use df\flex;
use df\iris;
use df\aura;
    
class Html extends iris\Translator {

    use core\string\THtmlStringEscapeHandler;

    public $html;
    public $lastNode;

    protected $_inSection = false;
    protected $_inBibliography = false;
    protected $_bibCount = 0;
    protected $_hasMath = false;
    protected $_references = array();
    protected $_imageDereferencer;

    public static function createLexer(iris\ISource $source) {
        return new flex\latex\Lexer($source);
    }

    public static function createParser(iris\ILexer $lexer) {
        return new flex\latex\Parser($lexer);   
    }

    public function translate() {
        foreach($this->unit->getEntities() as $document) {
            $this->_beginDocument($document);
            $this->_translateContainerNode($document);
            $this->_finaliseDocument($document);
        }

        return $this->html;
    }

    public function hasMath() {
        return $this->_hasMath;
    }

    public function setImageDereferencer(Callable $callback) {
        $this->_imageDereferencer = $callback;
        return $this;
    }

    public function getImageDereferencer() {
        return $this->_imageDereferencer;
    }

    protected function _dereferenceImage($id, $number) {
        if(!$this->_imageDereferencer) {
            throw new flex\latex\RuntimeException(
                'No image dereferencer has been defined'
            );
        }

        $d = $this->_imageDereferencer;
        return $d($id, $number);
    }

    protected function _beginDocument(flex\latex\map\Document $document) {
        //$this->html .= $this->element('h1', $document->getTitle())."\n";
        $refCounts = [];

        foreach($document->getReferenceMap() as $id => $item) {
            if(isset($this->_references[$id])) {
                continue;
            }

            if($item instanceof flex\latex\map\Block) {
                $type = $item->getType();
            } else {
                $parts = explode('\\', get_class($item));
                $type = lcfirst(array_pop($parts));
            }

            if(!isset($refCounts[$type])) {
                $refCounts[$type] = 0;
            }

            $this->_references[$id] = [
                'type' => $type,
                'body' => ++$refCounts[$type]
            ];
        }
    }

    protected function _translateContainerNode(flex\latex\IContainerNode $node) {
        $this->lastNode = null;

        foreach($node as $child) {
            $parts = explode('\\', get_class($child));
            $name = array_pop($parts);
            $func = '_translate'.ucfirst($name);

            if(method_exists($this, $func)) {
                $this->{$func}($child);
                $this->lastNode = $child;
            } else {
                core\dump($child);
            }
        }
    }

// Bibliography
    protected function _translateBibliography(flex\latex\map\Bibliography $bib) {
        if($this->_inSection) {
            $this->html .= '</section>'."\n";
            $this->_inSection = false;
        }

        $bibTag = $this->tag('section#bibliography');
        $this->_inBibliography = true;
        $this->html .= "\n".$bibTag->open().'<ol>'."\n";
        $this->_translateContainerNode($bib);
        $this->html .= '</ol>'.$bibTag->close()."\n";
        $this->_inBibliography = false;
    }


// Bibitem
    protected function _translateBibitemBlock(flex\latex\map\Block $block) {
        $this->_bibCount++;
        $liTag = $this->tag('li#bibitem-'.core\string\Manipulator::formatId($block->getId()));
        $this->html .= '    '.$liTag->open();
        $this->_translateContainerNode($block);
        $this->html .= $liTag->close()."\n";
    }


// Block
    protected function _translateBlock(flex\latex\map\Block $block) {
        $func = '_translate'.ucfirst($block->getType()).'Block';

        if(method_exists($this, $func)) {
            $this->{$func}($block);
        } else {
            core\dump('block', $block);
        }
    }

// Figure
    protected function _translateFigure(flex\latex\map\Figure $figure) {
        $alt = $captionTag = null;

        if($caption = $figure->getCaption()) {
            $captionTag = $this->tag('figcaption');
        }

        $id = core\string\Manipulator::formatId($figure->getId());
        $src = $this->_dereferenceImage($id, $figure->getNumber());

        if(!$alt) {
            $alt = core\string\Manipulator::formatLabel(core\io\Util::getFileName($src));
        }

        $figTag = $this->tag('figure', ['id' => $id]);
        $imgTag = $this->element('img', null, ['src' => $src, 'alt' => $alt]);

        $this->html .= "\n".$figTag->open()."\n";
        $this->html .= '    '.$imgTag."\n";

        if($captionTag) {
            $this->html .= '    '.$captionTag->open();
            $this->html .= $this->_translateContainerNode($caption);
            $this->html .= $captionTag->close()."\n";
        }

        $this->html .= $figTag->close()."\n";
    }

// Italic
    protected function _translateItalicBlock(flex\latex\map\Block $block) {
        $tag = $this->tag('em', ['class' => $block->getClasses()]);
        $this->html .= $tag->open();
        $this->_translateContainerNode($block);
        $this->html .= $tag->close();
    }

// Math
    protected function _translateMathNode(flex\latex\map\MathNode $math) {
        $this->_hasMath = true;


        if($math->isInline()) {
            $tag = $this->tag('span.math.inline');
            $this->html .= $tag->open();
            $this->html .= '\\('.$math->symbols.'\\)';
            $this->html .= $tag->close();
        } else {
            $tag = $this->tag('div.math.block');

            if($id = $math->getId()) {
                $tag->setId('mathNode-'.core\string\Manipulator::formatId($id));
            }

            $this->html .= "\n".$tag->open();
            $this->html .= '\\[';

            if($type = $math->getBlockType()) {
                $this->html .= '\\begin{'.$type.'}'."\n";
            }

            $this->html .= $math->symbols;

            if($type) {
                $this->html .= "\n".'\\end{'.$type.'}';
            }

            $this->html .= '\\]';
            $this->html .= $tag->close()."\n";
        } 
    }


// Ordered list
    protected function _translateOrderedListStructure(flex\latex\map\Structure $list) {
        if($id = $list->getId()) {
            $id = core\string\Manipulator::formatId($id);
        }

        $tag = $this->tag('ol', ['id' => $id]);
        $this->html .= $tag->open()."\n";

        foreach($list as $item) {
            $liTag = $this->tag('li');
            $this->html .= '    '.$liTag->open();
            $this->_translateContainerNode($item);
            $this->html .= $liTag->close()."\n";
        }

        $this->html .= $tag->close()."\n";
    }


// Paragraph
    protected function _translateParagraph(flex\latex\map\Paragraph $paragraph) {
        $tag = $this->tag('p', ['class' => $paragraph->getClasses()]);
        $this->html .= $tag->open();
        $this->_translateContainerNode($paragraph);
        $this->html .= $tag->close()."\n";
    }

// Reference
    protected function _translateReference(flex\latex\map\Reference $ref) {
        $id = $ref->getId();
        $htmlId = core\string\Manipulator::formatId($id);

        if(isset($this->_references[$id])) {
            $type = $this->_references[$id]['type'];
            $body = $this->_references[$id]['body'];
        } else {
            $type = $ref->getTargetType();
            $body = $ref->getType().': '.$htmlId;
        }

        switch($type) {
            case 'bibitem':
                $body = '['.$body.']';
                break;

            case 'figure':
                if(strtolower(substr(rtrim($this->html), -6)) == 'figure') {
                    $this->html = rtrim($this->html);
                    $temp = substr($this->html, -6);
                    $this->html = substr($this->html, 0, -6);
                    $body = $temp.' '.$body;
                }

                break;

            case 'mathNode':
                if(strtolower(substr(rtrim($this->html), -8)) == 'equation') {
                    $this->html = rtrim($this->html);
                    $temp = substr($this->html, -8);
                    $this->html = substr($this->html, 0, -8);
                    $body = $temp.' '.$body;
                }

                break;

            case 'table':
                if(strtolower(substr(rtrim($this->html), -5)) == 'table') {
                    $this->html = rtrim($this->html);
                    $temp = substr($this->html, -5);
                    $this->html = substr($this->html, 0, -5);
                    $body = $temp.' '.$body;
                }

                break;
        }

        if(substr($this->html, -1) == '>') {
            $this->html .= ' ';
        }

        $this->html .= $this->element('a.reference.'.$type, $body, ['href' => '#'.$type.'-'.$htmlId]);
    }

// Section
    protected function _translateSectionBlock(flex\latex\map\Block $block) {
        switch($block->getAttribute('level')) {
            case 1:
                if($this->_inSection) {
                    $this->html .= '</section>'."\n";
                    $this->_inSection = false;
                }

                $this->html .= "\n".'<section>'."\n";
                $this->_inSection = true;
                $tag = $this->tag('h2', ['id' => 'section-'.$block->getAttribute('number')]);

                $this->html .= $tag->open();
                $this->_translateContainerNode($block);
                $this->html .= $tag->close()."\n";
                break;

            case 2:
                if(!$this->_inSection) {
                    $this->html .= "\n".'<section>'."\n";
                    $this->_inSection = true;
                } else {
                    $this->html .= "\n";
                }

                $tag = $this->element('h3', ['id' => 'subsection-'.$block->getAttribute('number')]);
                $this->html .= $tag->open();
                $this->_translateContainerNode($block);
                $this->html .= $tag->close()."\n";
                break;

            case 3:
                if(!$this->_inSection) {
                    $this->html .= "\n".'<section>'."\n";
                    $this->_inSection = true;
                } else {
                    $this->html .= "\n";
                }

                $tag = $this->element('h4', ['id' => 'subsubsection-'.$block->getAttribute('number')]);
                $this->html .= $tag->open();
                $this->_translateContainerNode($block);
                $this->html .= $tag->close()."\n";
                break;

            default:
                core\dump('section', $block);
        }
    }


// Structure
    protected function _translateStructure(flex\latex\map\Structure $structure) {
        $func = '_translate'.ucfirst($structure->getType()).'Structure';

        if(method_exists($this, $func)) {
            $this->{$func}($structure);
        } else {
            core\dump('structure', $structure);
        }
    }


// Subheading
    protected function _translateSubheadingBlock(flex\latex\map\Block $block) {
        if($this->_inBibliography) {
            $this->html .= '</ol>'."\n";
        }

        $tag = $this->tag('h3');
        $this->html .= $tag->open();
        $this->_translateContainerNode($block);
        $this->html .= $tag->close()."\n";

        if($this->_inBibliography) {
            $this->html .= '<ol start="'.($this->_bibCount+1).'">'."\n";
        }
    }

// Table
    protected function _translateTable(flex\latex\map\Table $table) {
        if($rowHead = $table->isFirstRowHead()) {
            $colHead = false;
        } else {
            $colHead = $table->isFirstColumnHead();
        }

        $tableTag = $this->tag('table');

        if($id = $table->getId()) {
            $id = core\string\Manipulator::formatId($id);
            $tableTag->setId('table-'.$id);
        }

        $this->html .= "\n".$tableTag->open()."\n";

        if($caption = $table->getCaption()) {
            $captionTag = $this->tag('caption');
            $this->html .= $captionTag->open();
            $this->_translateContainerNode($caption);
            $this->html .= $captionTag->close()."\n";
        }

        $firstRow = true;

        foreach($table as $row) {
            if(!is_array($row)) {
                continue;
            }

            $rowTag = $this->tag('tr');

            if($firstRow && $rowHead) {
                $this->html .= '<thead>'."\n";
            }
                
            $this->html .= $rowTag->open()."\n";
            $firstCell = true;

            foreach($row as $cell) {
                $isHead = ($firstCell && $colHead)
                       || ($firstRow && $rowHead);

                $cellTag = $this->tag($isHead ? 'th' : 'td');
                $this->html .= '    '.$cellTag->open();

                if($isHead && !$cell->isEmpty()) {
                    $bodyCell = $cell->toArray()[0];
                } else {
                    $bodyCell = $cell;
                }

                $l = strlen($this->html);
                $this->_translateContainerNode($bodyCell);

                if($l == strlen($this->html)) {
                    $this->html .= '&nbsp;';
                }

                $this->html .= $cellTag->close()."\n";
                $firstCell = false;
            }

            $this->html .= $rowTag->close()."\n";

            if($firstRow && $rowHead) {
                $this->html .= '</thead>'."\n".'<tbody>'."\n";
            }

            $firstRow = false;
        }

        if($rowHead) {
            $this->html .= '<tbody>'."\n";
        }

        $this->html .= $tableTag->close()."\n";
    }

// Text node
    protected function _translateTextNode(flex\latex\map\TextNode $node) {
        $classes = $node->getClasses();
        $text = $node->getText();

        if(!empty($classes)) {
            $text = $this->element('span', $text, ['class' => $classes]);
        } else {
            $text = $this->esc($text);
        }

        $text = str_replace("\n", '<br />'."\n", $text);

        $this->html .= $text;
    }

    protected function _finaliseDocument($document) {
        if($this->_inSection) {
            $this->html .= '</section>'."\n";
            $this->_inSection = false;
        }
    }

    protected function tag($tag, array $attributes=array()) {
        return new aura\html\Tag($tag, $attributes);
    }

    protected function element($tag, $content, array $attributes=array()) {
        return new aura\html\Element($tag, $content, $attributes);
    }
}