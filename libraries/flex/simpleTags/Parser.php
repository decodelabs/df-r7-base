<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\simpleTags;

use df;
use df\core;
use df\flex;
use df\aura;
use df\arch;

class Parser implements flex\IInlineHtmlProducer {

    use flex\TParser;

    const TAG_LIST = [
        'a', 'abbr', 'b', 'br', 'cite', 'code', 'del', 'em',
        'i', 'img', 'ins', 'q', 'small', 'span', 'strong',
        'sub', 'sup', 'time', 'u', 'var'
    ];

    const EXTENDED_TAG_LIST = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'dl', 'dt', 'dd',
        'p', 'br', 'hr', 'pre', 'blockquote',
        'address', 'figure', 'figcaption'
    ];

    protected $_extended = false;

    public function __construct(?string $source, bool $extended=false) {
        $this->source = (string)$source;
        $this->_extended = $extended;
    }


// Translate
    public function toHtml() {
        if(null === ($text = $this->_prepareSource($this->_extended))) {
            return null;
        }

        if($this->_extended) {
            return $this->_blockify($text);
        } else {
            return $this->_inlineBlockify($text);
        }
    }

    public function toInlineHtml() {
        if(null === ($text = $this->_prepareSource(false))) {
            return null;
        }

        return str_replace("\n", '<br />'."\n", $text);
    }


// Preare source
    protected function _prepareSource(bool $extended) {
        $text = new flex\Text(trim($this->source));

        if($text->isEmpty()) {
            return null;
        }

        $tags = [];

        foreach(self::TAG_LIST as $tag) {
            $tags[] = '<'.$tag.'>';
        }

        if($extended) {
            foreach(self::EXTENDED_TAG_LIST as $tag) {
                $tags[] = '<'.$tag.'>';
            }
        }

        $context = arch\Context::getCurrent();

        return $text

            // Strip tags
            ->stripTags(implode('', $tags))

            // Sort out spaces
            ->regexReplace('/(\s) /', '$1&nbsp;')
            ->regexReplace('/ (\s)/', '&nbsp;$1')

            // Urls
            ->regexReplace('/ (href|src)\=\"([^\"]+)\"/', function($matches) use($context) {
                return ' '.$matches[1].'="'.htmlspecialchars((string)$context->uri->__invoke($matches[2])).'"';
            })

            ->toString();
    }


// Inline tags blockify
    protected function _inlineBlockify(string $text): string {
        if(!strlen(trim($text))) {
            return '';
        }

        $parts = (new flex\Text($text))

            // Double <br>s
            ->regexReplace('|<br\s*/?>\s*<br\s*/?>|', "\n\n")

            // Standardise new lines
            ->replace(["\r\n", "\r"], "\n")
            ->regexReplace("/\n\n+/", "\n\n")

            // Split
            ->regexSplit('/\n\s*\n/', -1, \PREG_SPLIT_NO_EMPTY);

        $text = '';

        foreach($parts as $part) {
            $text .= '<p>'.trim($part).'</p>'."\n";
        }

        return (new flex\Text($text))

            // Remove empties
            ->regexReplace('|<p>\s*</p>|', '')

            // Normalize new lines
            ->regexReplace("|\n</p>$|", '</p>')

            ->toString();
    }


// Extended blockify
    protected function _blockify(string $text): string {
        if(!strlen(trim($text))) {
            return '';
        }

        $text = rtrim($text)."\n";
        $preTags = [];

        if(strpos($text, '<pre') !== false) {
            $parts = explode('</pre>', $text);
            $last = array_pop($parts);
            $text = '';
            $i = 0;

            foreach($parts as $part) {
                if(false === ($start = strpos($part, '<pre'))) {
                    $text .= $part;
                    continue;
                }

                $name = '<pre st-placeholder-'.$i.'></pre>';
                $preTags[$name] = substr($part, $start).'</pre>';
                $text .= substr($part, 0, $start).$name;
                $i++;
            }

            $text .= $last;
        }

        $blockReg = '(?:'.implode('|', self::EXTENDED_TAG_LIST).')';

        $parts = (new flex\Text($text))

            // Double <br>s
            ->regexReplace('|<br\s*/?>\s*<br\s*/?>|', "\n\n")

            // Line break around block elements
            ->regexReplace('!(<'.$blockReg.'[\s/>])!', "\n\n".'$1')
            ->regexReplace('!(</'.$blockReg.'>)!', '$1'."\n\n")

            // Standardise new lines
            ->replace(["\r\n", "\r"], "\n")
            ->regexReplace("/\n\n+/", "\n\n")

            // Split
            ->regexSplit('/\n\s*\n/', -1, \PREG_SPLIT_NO_EMPTY);

        $text = '';

        foreach($parts as $part) {
            $part = trim($part);

            if(!preg_match('!</?'.$blockReg.'[^>]*>!', $part)) {
                $part = '<p>'.$part.'</p>';
            }

            $text .= $part."\n";
        }

        $text = (new flex\Text($text))

            // Remove empties
            ->regexReplace('|<p>\s*</p>|', '')

            // Normalize blocks;
            ->regexReplace('!<p>\s*(</?'.$blockReg.'[^>]*>)!', '$1')
            ->regexReplace('!(</?'.$blockReg.'[^>]*>)\s*</p>!', '$1')

            // Normalize <br>s
            ->replace(['<br>', '<br/>'], '<br />')
            ->regexReplace('|(?<!<br />)\s*\n|', "<br />\n")
            ->regexReplace('!(</?'.$blockReg.'[^>]*>)\s*<br />!', '$1')
            ->regexReplace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1')
            ->regexReplace("|\n</p>$|", '</p>')

            ->toString();

        // Replace pres
        if(!empty($preTags)) {
            $text = str_replace(array_keys($preTags), array_values($preTags), $text);
        }

        return $text;
    }
}
