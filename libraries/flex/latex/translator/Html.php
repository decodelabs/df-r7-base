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

    public $document;
    public $lastNode;
    public $buffer = '';

    protected $_inSection = false;
    protected $_inBibliography = false;
    protected $_bibCount = 0;
    protected $_hasMath = false;
    protected $_references = [];
    protected $_imageDereferencer;
    protected $_translateUrls = true;

    public static function createLexer(iris\ISource $source) {
        return new flex\latex\Lexer($source);
    }

    public static function createParser(iris\ILexer $lexer) {
        return new flex\latex\Parser($lexer);   
    }

    public function translate() {
        $output = '';

        foreach($this->unit->getEntities() as $document) {
            $this->_beginDocument($document);
            $output .= $this->_translateContainerNode($document);
            $output .= $this->_finaliseDocument($document);
            $this->buffer = '';
        }

        $output = preg_replace('/(\(\<a[^>]+\>[^<]+\<\/a\>\))/i', '<span class="no-wrap">$1</span>', $output);

        return $output;
    }

    public function hasMath() {
        return $this->_hasMath;
    }

    public function setImageDereferencer($callback) {
        $this->_imageDereferencer = core\lang\Callback::factory($callback);
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

        return $this->_imageDereferencer->invoke($id, $number);
    }

    protected function _beginDocument(flex\latex\map\Document $document) {
        //$this->html .= $this->element('h1', $document->getTitle())."\n";
        $refCounts = [];
        $this->document = $document;

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
        $lastBuffer = $this->buffer;
        $this->buffer = '';
        $this->lastNode = null;

        foreach($node as $child) {
            $parts = explode('\\', get_class($child));
            $name = array_pop($parts);
            $func = '_translate'.ucfirst($name);

            if(method_exists($this, $func)) {
                $this->buffer .= $this->{$func}($child);
                $this->lastNode = $child;
            } else {
                core\dump($child);
            }
        }

        $output = $this->buffer;
        $this->buffer = $lastBuffer;

        return $output;
    }

// Align
    protected function _translateAlignBlock(flex\latex\map\Block $block) {
        return $this->_translateContainerNode($block);
    }

// Bibliography
    protected function _translateBibliography(flex\latex\map\Bibliography $bib) {
        $output = '';

        if($this->_inSection) {
            $output .= '</section>'."\n";
            $this->_inSection = false;
        }

        $bibTag = $this->tag('section#bibliography');
        $this->_inBibliography = true;

        $output .= "\n".$bibTag->open().'<ol>'."\n";
        $output .= $this->_translateContainerNode($bib);
        $output .= '</ol>'.$bibTag->close()."\n";

        $this->_inBibliography = false;

        return $output;
    }


// Bibitem
    protected function _translateBibitemBlock(flex\latex\map\Block $block) {
        $output = '';
        $this->_bibCount++;

        $liTag = $this->tag('li#bibitem-'.core\string\Manipulator::formatId($block->getId()));

        $output .= '    '.$liTag->open();
        $output .= $this->_translateContainerNode($block);
        $output .= $liTag->close()."\n";

        return $output;
    }


// Block
    protected function _translateBlock(flex\latex\map\Block $block) {
        $func = '_translate'.ucfirst($block->getType()).'Block';

        if(method_exists($this, $func)) {
            return $this->{$func}($block);
        } else {
            core\dump('block', $block);
        }
    }


// Bold
    protected function _translateBoldBlock(flex\latex\map\Block $block) {
        $output = '';
        $tag = $this->tag('strong', ['class' => $block->getClasses()]);

        $output .= $tag->open();
        $output .= $this->_translateContainerNode($block);
        $output .= $tag->close();

        return $output;
    }


// Figure
    protected function _translateFigure(flex\latex\map\Figure $figure) {
        $output = '';
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

        $output .= "\n".$figTag->open()."\n";
        $output .= '    '.$imgTag."\n";

        if($captionTag) {
            $output .= '    '.$this->element('h4', 'Figure '.$figure->getNumber())."\n";
            $output .= '    '.$captionTag->open();
            $output .= $this->_translateContainerNode($caption);
            $output .= $captionTag->close()."\n";
        }

        $output .= $figTag->close()."\n";
        return $output;
    }

// Href
    protected function _translateHrefBlock(flex\latex\map\Block $block) {
        $urls = $this->_translateUrls;
        $this->_translateUrls = false;

        $output = $this->element('a', $this->_translateContainerNode($block), [
            'href' => (string)$block->getAttribute('href'),
            'target' => '_blank'
        ]);

        $this->_translateUrls = $urls;
        return $output;
    }

// Italic
    protected function _translateItalicBlock(flex\latex\map\Block $block) {
        $output = '';
        $tag = $this->tag('em', ['class' => $block->getClasses()]);

        $output .= $tag->open();
        $output .= $this->_translateContainerNode($block);
        $output .= $tag->close();

        return $output;
    }

// Math
    protected function _translateMathNode(flex\latex\map\MathNode $math) {
        $this->_hasMath = true;
        $output = '';


        if($math->isInline()) {
            $tag = $this->tag('span.math.inline');
            $output .= $tag->open();
            $output .= '\\('.$math->symbols.'\\)';
            $output .= $tag->close();
        } else {
            $tag = $this->tag('div.math.block');

            if($id = $math->getId()) {
                $tag->setId('mathNode-'.core\string\Manipulator::formatId($id));
            }

            $output .= "\n".$tag->open();
            $output .= '    '.$this->element('h4', '('.$math->getNumber().')')."\n";
            $output .= '\\[';

            if($type = $math->getBlockType()) {
                $output .= '\\begin{'.$type.'}'."\n";
            }

            $output .= $math->symbols;

            if($type) {
                $output .= "\n".'\\end{'.$type.'}';
            }

            $output .= '\\]';
            $output .= $tag->close()."\n";
        } 

        return $output;
    }


// Ordered list
    protected function _translateOrderedListStructure(flex\latex\map\Structure $list) {
        $output = '';

        if($id = $list->getId()) {
            $id = core\string\Manipulator::formatId($id);
        }

        $tag = $this->tag('ol', ['id' => $id]);
        $output .= $tag->open()."\n";

        foreach($list as $item) {
            $liTag = $this->tag('li');

            $output .= '    '.$liTag->open();
            $output .= $this->_translateContainerNode($item);
            $output .= $liTag->close()."\n";
        }

        $output .= $tag->close()."\n";
        return $output;
    }

// Unordered list
    protected function _translateUnorderedListStructure(flex\latex\map\Structure $list) {
        $output = '';

        if($id = $list->getId()) {
            $id = core\string\Manipulator::formatId($id);
        }

        $tag = $this->tag('ul', ['id' => $id]);
        $output .= $tag->open()."\n";

        foreach($list as $item) {
            $liTag = $this->tag('li');

            $output .= '    '.$liTag->open();
            $output .= $this->_translateContainerNode($item);
            $output .= $liTag->close()."\n";
        }

        $output .= $tag->close()."\n";
        return $output;
    }


// Paragraph
    protected function _translateParagraph(flex\latex\map\Paragraph $paragraph) {
        $output = '';
        $tag = $this->tag('p', ['class' => $paragraph->getClasses()]);

        $output .= $tag->open();
        $output .= $this->_translateContainerNode($paragraph);
        $output .= $tag->close()."\n";

        return $output;
    }

// Reference
    protected function _translateReference(flex\latex\map\Reference $ref) {
        $output = '';
        $id = $ref->getId();
        $htmlId = core\string\Manipulator::formatId($id);

        if(isset($this->_references[$id])) {
            $type = $this->_references[$id]['type'];
            $body = $this->_references[$id]['body'];
        } else {
            $type = $ref->getTargetType();
            $body = $htmlId;
        }

        $stripBuffer = strtolower(rtrim($this->buffer));

        switch($type) {
            case 'bibitem':
                $this->_fixBufferLinkSpacing();
                $body = '['.$body.']';
                break;

            case 'figure':
                if(substr($stripBuffer, -6) == 'figure') {
                    $this->buffer = rtrim($this->buffer);
                    $prefix = substr($this->buffer, -6);
                    $this->buffer = substr($this->buffer, 0, -6);
                } else {
                    $this->_fixBufferLinkSpacing();
                    $prefix = 'Figure';
                }

                if($this->_isBufferInParagraph($stripBuffer)) {
                    $prefix = strtolower($prefix);
                }

                $body = $prefix.' '.$body;
                break;

            case 'mathNode':
                if(substr($stripBuffer, -8) == 'equation') {
                    $this->buffer = rtrim($this->buffer);
                    $prefix = substr($this->buffer, -8);
                    $this->buffer = substr($this->buffer, 0, -8);
                } else {
                    $this->_fixBufferLinkSpacing();
                    $prefix = 'Equation';
                }

                if($this->_isBufferInParagraph($stripBuffer)) {
                    $prefix = strtolower($prefix);
                }

                $body = $prefix.' '.$body;
                break;

            case 'table':
                if(substr($stripBuffer, -5) == 'table') {
                    $this->buffer = rtrim($this->buffer);
                    $prefix = substr($this->buffer, -5);
                    $this->buffer = substr($this->buffer, 0, -5);
                } else {
                    $this->_fixBufferLinkSpacing();
                    $prefix = 'Table';
                }

                if($this->_isBufferInParagraph($stripBuffer)) {
                    $prefix = strtolower($prefix);
                }

                $body = $prefix.' '.$body;
                break;
        }

        if(substr($this->buffer, -1) == '>') {
            $this->buffer .= ' ';
        }

        $output .= $this->element('a.reference.'.$type, $body, ['href' => '#'.$type.'-'.$htmlId]);
        return $output;
    }

    protected function _fixBufferLinkSpacing() {
        if(!strpbrk(substr($this->buffer, -1), '({[=| ')) {
            $this->buffer .= ' ';
        }
    }

    protected function _isBufferInParagraph($buffer) {
        if(substr(rtrim($this->buffer), -1) == '.') {
            return false;
        }

        return preg_match('/[a-zA-Z0-9\(\)\[\]\,\<\>_\-]$/i', $buffer);
    }

// Section
    protected function _translateSectionBlock(flex\latex\map\Block $block) {
        $output = '';

        switch($block->getAttribute('level')) {
            case 1:
                if($this->_inSection) {
                    $output .= '</section>'."\n";
                    $this->_inSection = false;
                }

                $output .= "\n".'<section>'."\n";
                $this->_inSection = true;
                $tag = $this->tag('h2', ['id' => 'section-'.$block->getNumber()]);

                $output .= $tag->open();
                $output .= $this->_translateContainerNode($block);
                $output .= $tag->close()."\n";
                break;

            case 2:
                if(!$this->_inSection) {
                    $output .= "\n".'<section>'."\n";
                    $this->_inSection = true;
                } else {
                    $output .= "\n";
                }

                $tag = $this->element('h3', ['id' => 'subsection-'.$block->getNumber()]);
                $output .= $tag->open();
                $output .= $this->_translateContainerNode($block);
                $output .= $tag->close()."\n";
                break;

            case 3:
                if(!$this->_inSection) {
                    $output .= "\n".'<section>'."\n";
                    $this->_inSection = true;
                } else {
                    $output .= "\n";
                }

                $tag = $this->element('h4', ['id' => 'subsubsection-'.$block->getNumber()]);
                $output .= $tag->open();
                $output .= $this->_translateContainerNode($block);
                $output .= $tag->close()."\n";
                break;

            default:
                core\dump('section', $block);
        }

        return $output;
    }


// Small
    protected function _translateSmallBlock(flex\latex\map\Block $block) {
        return (string)$this->element(
            'small', 
            $this->string($this->_translateContainerNode($block)), 
            ['class' => $block->getClasses()]
        );
    }


// Structure
    protected function _translateStructure(flex\latex\map\Structure $structure) {
        $func = '_translate'.ucfirst($structure->getType()).'Structure';

        if(method_exists($this, $func)) {
            return $this->{$func}($structure);
        } else {
            core\dump('structure', $structure);
        }
    }


// Subheading
    protected function _translateSubheadingBlock(flex\latex\map\Block $block) {
        $output = '';
        if($this->_inBibliography) {
            $output .= '</ol>'."\n";
        }

        $tag = $this->tag('h3');
        $output .= $tag->open();
        $output .= $this->_translateContainerNode($block);
        $output .= $tag->close()."\n";

        if($this->_inBibliography) {
            $output .= '<ol start="'.($this->_bibCount+1).'">'."\n";
        }

        return $output;
    }

// Table
    protected function _translateTable(flex\latex\map\Table $table) {
        $output = '';

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

        $output .= "\n".$tableTag->open()."\n";
        $captionContent = null;

        if($caption = $table->getCaption()) {
            $captionContent = $this->_translateContainerNode($caption);
        }

        if($number = $table->getNumber()) {
            $captionContent = trim($this->element('h4', 'Table '.$number).' '.$captionContent);
        }

        if($captionContent) {
            $captionTag = $this->tag('caption');
            $output .= $captionTag->open();
            $output .= $captionContent;
            $output .= $captionTag->close()."\n";
        }

        $firstRow = true;

        foreach($table as $row) {
            if(!is_array($row)) {
                continue;
            }

            $rowTag = $this->tag('tr');

            if($firstRow && $rowHead) {
                $output .= '<thead>'."\n";
            }
                
            $output .= $rowTag->open()."\n";
            $firstCell = true;

            foreach($row as $cell) {
                $isHead = ($firstCell && $colHead)
                       || ($firstRow && $rowHead);

                $cellTag = $this->tag($isHead ? 'th' : 'td');

                if($cell->hasAttribute('colspan')) {
                    $cellTag->setAttribute('colspan', $cell->getAttribute('colspan'));
                }

                if($cell->hasAttribute('rowspan')) {
                    $cellTag->setAttribute('rowspan', $cell->getAttribute('rowspan'));
                }

                $output .= '    '.$cellTag->open();

                if($isHead && !$cell->isEmpty()) {
                    $bodyCell = $cell->toArray()[0];
                } else {
                    $bodyCell = $cell;
                }

                $cellContent = $this->_translateContainerNode($bodyCell);

                if(!strlen($cellContent)) {
                    $cellContent = '&nbsp;';
                }

                $output .= $cellContent;
                $output .= $cellTag->close()."\n";
                $firstCell = false;
            }

            $output .= $rowTag->close()."\n";

            if($firstRow && $rowHead) {
                $output .= '</thead>'."\n".'<tbody>'."\n";
            }

            $firstRow = false;
        }

        if($rowHead) {
            $output .= '<tbody>'."\n";
        }

        $output .= $tableTag->close()."\n";
        return $output;
    }

// Text node
    protected function _translateTextNode(flex\latex\map\TextNode $node) {
        $classes = $node->getClasses();
        $text = $node->getText();

        if(!empty($classes)) {
            $text = $this->element('span', $text, ['class' => $classes]);

            if($text->hasClass('superscript')) {
                $text->setName('sup');
            } else if($text->hasClass('subscript')) {
                $text->setName('sub');
            }
        } else {
            $text = $this->esc($text);
        }

        $text = str_replace("\n", '<br />'."\n", $text);

        if($this->_translateUrls) {
            $text = preg_replace_callback(
                '/(http(s)?\:\/\/[^\s\<\>]+)/',
                function($matches) {
                    $url = htmlspecialchars(trim($matches[1], ';:. '));
                    return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
                },
                $text
            );

            $text = preg_replace_callback(
                '/([^\s@\<\>\(\)\[\]\:\;]+@[^\s@\<\>\(\)\[\]\:\;\.]+\.[^\s@\<\>\(\)\[\]\:\;]+)/', 
                function($matches) {
                    $email = htmlspecialchars(trim($matches[1], '. '));
                    return '<a href="mailto:'.$email.'">'.$email.'</a>';
                }, 
                $text
            );
        }

        return $text;
    }


// Tiny
    protected function _translateTinyBlock(flex\latex\map\Block $block) {
        return (string)$this->element(
                'small', 
                $this->string($this->_translateContainerNode($block)), 
                ['class' => $block->getClasses()]
            )
            ->addClass('tiny');
    }

    protected function _finaliseDocument($document) {
        $output = '';

        if($this->_inSection) {
            $output .= '</section>'."\n";
            $this->_inSection = false;
        }

        return $output;
    }

    protected function tag($tag, array $attributes=[]) {
        return new aura\html\Tag($tag, $attributes);
    }

    protected function element($tag, $content, array $attributes=[]) {
        return new aura\html\Element($tag, $content, $attributes);
    }

    protected function string($html) {
        return new aura\html\ElementString($html);
    }

    protected function containerElement($tag, flex\latex\IContainerNode $node, array $attributes=[]) {
        $tag = $this->tag($tag, $attributes);
        return $tag->open().$this->_translateContainerNode($node).$tag->close();
    }
}