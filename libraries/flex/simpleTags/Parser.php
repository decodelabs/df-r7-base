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

use DecodeLabs\Dictum;

class Parser implements flex\IInlineHtmlProducer
{
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

    const CONTAINER_TAG_LIST = [
        'ul', 'ol',
        'table', 'thead', 'tbody', 'tr'
    ];

    protected $_extended = false;

    public function __construct(?string $source, bool $extended=false)
    {
        $this->source = (string)$source;
        $this->_extended = $extended;
    }


    // Translate
    public function toHtml()
    {
        if (null === ($text = $this->_prepareSource($this->_extended))) {
            return null;
        }

        return $this->_blockify($text, $this->_extended);
    }

    public function toInlineHtml()
    {
        if (null === ($text = $this->_prepareSource(false))) {
            return null;
        }

        return str_replace("\n", '<br />'."\n", $text);
    }


    // Preare source
    protected function _prepareSource(bool $extended)
    {
        $text = trim($this->source);

        if (!strlen($text)) {
            return null;
        }

        $tags = [];

        foreach (self::TAG_LIST as $tag) {
            $tags[] = '<'.$tag.'>';
        }

        if ($extended) {
            foreach (self::EXTENDED_TAG_LIST as $tag) {
                $tags[] = '<'.$tag.'>';
            }
        }

        $context = arch\Context::getCurrent();

        // Strip tags
        $text = strip_tags($text, implode('', $tags));

        // Sort out spaces
        if (!$extended) {
            $text = preg_replace('/(\s) /', '$1&nbsp;', $text);
            $text = preg_replace('/ (\s)/', '&nbsp;$1', $text);
        }

        // Urls
        $text = preg_replace_callback('/ (href|src)\=\"([^\"]+)\"/', function ($matches) use ($context) {
            return ' '.$matches[1].'="'.htmlspecialchars((string)$context->uri->__invoke($matches[2])).'"';
        }, $text);

        return $text;
    }


    // Extended blockify
    protected function _blockify(string $text, bool $extended=true): string
    {
        if (!strlen(trim($text))) {
            return '';
        }

        $text = rtrim($text)."\n";
        $preTags = [];

        if ($extended && strpos($text, '<pre') !== false) {
            $parts = explode('</pre>', $text);
            $last = array_pop($parts);
            $text = '';
            $i = 0;

            foreach ($parts as $part) {
                if (false === ($start = strpos($part, '<pre'))) {
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

        if ($extended) {
            $blockReg = '(?:'.implode('|', self::EXTENDED_TAG_LIST).')';
        } else {
            $blockReg = '(?:p)';
        }

        $containerReg = '(?:'.implode('|', self::CONTAINER_TAG_LIST).')';

        // Double <br>s
        $text = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $text);

        // Line break around block elements
        $text = preg_replace('!(<'.$blockReg.'[\s/>])!', "\n\n".'$1', $text);
        $text = preg_replace('!(</'.$blockReg.'>)!', '$1'."\n\n", $text);

        // Standardise new lines
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n\n+/", "\n\n", $text);

        // Split
        $parts = preg_split('/\n\s*\n/', $text, -1, \PREG_SPLIT_NO_EMPTY);
        $text = '';

        foreach ($parts as $part) {
            $part = trim($part);

            if ($extended && !preg_match('!</?'.$containerReg.'[^>]*>!', $part)) {
                // Sort out spaces
                $part = preg_replace('/(\s) /', '$1&nbsp;', $part);
                $part = preg_replace('/ (\s)/', '&nbsp;$1', $part);
            }

            if (!preg_match('!</?'.$blockReg.'[^>]*>!', $part)) {
                $part = '<p>'.$part.'</p>';
            }

            $text .= $part."\n";
        }



        // Remove empties
        $text = preg_replace('|<p>\s*</p>|', '', $text);

        // Normalize blocks;
        $text = preg_replace('!<p>\s*(</?'.$blockReg.'[^>]*>)!', '$1', $text);
        $text = preg_replace('!(</?'.$blockReg.'[^>]*>)\s*</p>!', '$1', $text);

        // Normalize <br>s
        $text = str_replace(['<br>', '<br/>'], '<br />', $text);
        $text = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $text);
        $text = preg_replace('!(</?'.$blockReg.'[^>]*>)\s*<br />!', '$1', $text);
        $text = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $text);
        $text = preg_replace("|\n</p>$|", '</p>', $text);


        // Replace pres
        if ($extended && !empty($preTags)) {
            $text = str_replace(array_keys($preTags), array_values($preTags), $text);
        }

        return $text;
    }
}
