<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\markdown;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flex;

class Parser implements flex\IHtmlProducer {

    use flex\TParser;

    const INLINE_MARKERS = '!"*_&[:<>`~\\';
    const SPECIAL_CHARACTERS = '\\`*_{}[]()>#+-.!|';
    const ATTRIBUTE_REGEX = '[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:[^"\'=<>`\s]+|"[^"]*"|\'[^\']*\'))?';

    protected static $_blockTypes = [
        '#' => ['Header'],
        '*' => ['Rule', 'List'],
        '+' => ['List'],
        '-' => ['SetextHeader', 'Table', 'Rule', 'List'],
        '0' => ['List'],
        '1' => ['List'],
        '2' => ['List'],
        '3' => ['List'],
        '4' => ['List'],
        '5' => ['List'],
        '6' => ['List'],
        '7' => ['List'],
        '8' => ['List'],
        '9' => ['List'],
        ':' => ['Table'],
        '<' => ['Comment', 'Markup'],
        '=' => ['SetextHeader'],
        '>' => ['Quote'],
        '[' => ['Reference'],
        '_' => ['Rule'],
        '`' => ['FencedCode'],
        '|' => ['Table'],
        '~' => ['FencedCode']
    ];

    protected static $_unmarkedBlockTypes = ['Code'];

    protected static $_inlineTypes = [
        '"' => ['SpecialCharacter'],
        '!' => ['Image'],
        '&' => ['SpecialCharacter'],
        '*' => ['Emphasis'],
        ':' => ['Url'],
        '<' => ['UrlTag', 'EmailTag', 'Markup', 'SpecialCharacter'],
        '>' => ['SpecialCharacter'],
        '[' => ['Link'],
        '_' => ['Emphasis'],
        '`' => ['Code'],
        '~' => ['Strikethrough'],
        '\\' => ['EscapeSequence']
    ];

    protected static $_strongRegex = [
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
    ];

    protected static $_emRegex = [
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];



    protected $_context;
    protected $_definitions = [];

    protected $_escapeMarkup = false;

    public function shouldEscapeMarkup($flag=null) {
        if($flag !== null) {
            $this->_escapeMarkup = (bool)$flag;
            return $this;
        }

        return $this->_escapeMarkup;
    }

    public function toHtml() {
        $this->_context = arch\Context::getCurrent();
        $this->_definitions = [];
        return $this->_handleLines(flex\Delimited::splitLines($this->source));
    }

    protected function _scanLines($lines) {
        $currentBlock = null;
        $blocks = [];

        foreach($lines as $line) {
            while(count($blocks) > 1) {
                yield array_shift($blocks);
            }

            if(!strlen(rtrim($line))) {
                if($currentBlock) {
                    $currentBlock->isInterrupted = true;
                }

                continue;
            }

            // tabs
            if(false !== strpos($line, "\t")) {
                $parts = explode("\t", $line);
                $line = array_shift($parts);

                foreach($parts as $part) {
                    $spaces = 4 - mb_strlen($line, 'utf-8') % 4;
                    $line .= str_repeat(' ', $spaces).$part;
                }
            }

            // indent
            $indent = 0;

            while(isset($line{$indent}) && $line{$indent} === ' ') {
                $indent++;
            }

            $line = new Parser_Line([
                'indent' => $indent,
                'body' => $line,
                'text' => $indent > 0 ? substr($line, $indent) : $line
            ]);


            // incomplete
            if($currentBlock && !$currentBlock->isComplete) {
                $func = '_continue'.$currentBlock->type.'Block';
                $block = null;

                if(method_exists($this, $func)) {
                    $block = $this->{$func}($line, $currentBlock);
                }

                if($block) {
                    $currentBlock = $block;
                    continue;
                } else {
                    $currentBlock->isComplete = true;
                }
            }


            // type
            $marker = substr($line->text, 0, 1);
            $blockTypes = self::$_unmarkedBlockTypes;

            if(isset(self::$_blockTypes[$marker])) {
                $blockTypes = array_merge($blockTypes, self::$_blockTypes[$marker]);
            }


            // begin
            foreach($blockTypes as $blockType) {
                $block = $this->{'_begin'.$blockType.'Block'}($line, $currentBlock);

                if(!$block) {
                    continue;
                }

                $block->type = $blockType;

                if(!$block->isIdentified) {
                    if($currentBlock) {
                        $blocks[] = $currentBlock;
                    }

                    $block->isIdentified = true;
                }

                $currentBlock = $block;
                continue 2;
            }


            if($currentBlock && !$currentBlock->type && !$currentBlock->isInterrupted) {
                $currentBlock->element->content .= "\n".$line->text;
            } else {
                if($currentBlock) {
                    $blocks[] = $currentBlock;
                }

                $currentBlock = $this->_beginParagraphBlock($line);
                $currentBlock->isIdentified = true;
            }
        }

        if(!$currentBlock->isComplete) {
            $currentBlock->isComplete = true;
        }

        if($currentBlock) {
            $blocks[] = $currentBlock;
        }

        while(!empty($blocks)) {
            yield array_shift($blocks);
        }
    }




// Code block
    protected function _beginCodeBlock(Parser_Line $line, Parser_Block $block=null) {
        if($block && !$block->type && !$block->isInterrupted) {
            return;
        }

        if($line->indent < 4) {
            return;
        }

        $text = substr($line->body, 4);

        return new Parser_Block([
            'element' => new Parser_Element([
                'name' => 'pre',
                'handler' => 'element',
                'content' => new Parser_Element([
                    'name' => 'code',
                    'content' => $text
                ])
            ])
        ]);
    }

    protected function _continueCodeBlock(Parser_Line $line, Parser_Block $block) {
        if($line->indent < 4) {
            return;
        }

        if($block->isInterrupted) {
            $block->element->content->content .= "\n";
            $block->isInterrupted = false;
        }

        $block->element->content->content .= "\n".substr($line->body, 4);
        return $block;
    }



// Comment block
    protected function _beginCommentBlock(Parser_Line $line, Parser_Block $block=null) {
        if(0 !== strpos($line->text, '<!--')) {
            return;
        }

        $block = new Parser_Block([
            'markup' => $line->body
        ]);

        if(preg_match('/-->$/', $line->text)) {
            $block->isComplete = true;
        }

        return $block;
    }

    protected function _continueCommentBlock(Parser_Line $line, Parser_Block $block) {
        if($block->isComplete) {
            return;
        }

        $block->markup .= "\n".$line->body;

        if(preg_match('/-->$/', $line->text)) {
            $block->isComplete = true;
        }

        return $block;
    }


// Fenced code
    protected function _beginFencedCodeBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!preg_match('/^(['.$line->text{0}.']{3,})[ ]*([\w-]+)?[ ]*$/', $line->text, $matches)) {
            return;
        }

        $element = new Parser_Element([
            'name' => 'pre',
            'handler' => 'element',
            'type' => 'code',
            'content' => ''
        ]);

        if(isset($matches[2])) {
            $class = 'language-'.$matches[2];
            $element->attributes = ['class' => $class];
        }

        return new Parser_Block([
            'char' => $line->text{0},
            'element' => $element
        ]);
    }

    protected function _continueFencedCodeBlock(Parser_Line $line, Parser_Block $block) {
        if($block->isComplete) {
            return;
        }

        if($block->isInterrupted) {
            $block->element->content .= "\n";
            $block->isInterrupted = false;
        }

        if(preg_match('/^'.$block->char.'{3,}[ ]*$/', $line->text)) {
            $block->element->content = substr($block->element->content, 1);
            $block->isComplete = true;
            return $block;
        }

        $block->element->content .= "\n".$line->body;
        return $block;
    }



// Header
    protected function _beginHeaderBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!isset($line->text{1})) {
            return;
        }

        $level = 1;

        while(isset($line->text{$level}) && $line->text{$level} === '#') {
            $level++;
        }

        if($level > 6) {
            return;
        }

        return new Parser_Block([
            'element' => new Parser_Element([
                'name' => 'h'.min(6, $level),
                'content' => trim($line->text, '# '),
                'handler' => 'line'
            ])
        ]);
    }



// List
    protected function _beginListBlock(Parser_Line $line, Parser_Block $block=null) {
        if($line->text{0} <= '-') {
            $name = 'ul';
            $pattern = '[*+-]';
        } else {
            $name = 'ol';
            $pattern = '[0-9]+[.]';
        }

        if(!preg_match('/^('.$pattern.'[ ]+)(.*)/', $line->text, $matches)) {
            return;
        }

        $li = new Parser_Element([
            'name' => 'li',
            'handler' => 'listItem',
            'content' => [$matches[2]]
        ]);

        return new Parser_Block([
            'indent' => $line->indent,
            'pattern' => $pattern,
            'element' => new Parser_Element([
                'name' => $name,
                'handler' => 'elements',
                'content' => [$li]
            ]),
            'currentLi' => $li
        ]);
    }

    protected function _continueListBlock(Parser_Line $line, Parser_Block $block) {
        if($block->indent === $line->indent
        && preg_match('/^'.$block->pattern.'(?:[ ]+(.*)|$)/', $line->text, $matches)) {
            if($block->isInterrupted) {
                $block->isInterrupted = false;
                $block->currentLi->content[] = '';
            }

            $block->currentLi = null;
            $text = isset($matches[1]) ? $matches[1] : '';

            $block->element->content[] = new Parser_Element([
                'name' => 'li',
                'handler' => 'listItem',
                'content' => [$text]
            ]);

            return $block;
        }

        if($line->text{0} === '['
        && $this->_beginReferenceBlock($line)) {
            return $block;
        }

        if(!$block->isInterrupted) {
            $text = preg_replace('/^[ ]{0,4}/', '', $line->body);
            $block->currentLi->content[] = $text;
            return $block;
        }

        if($line->indent > 0) {
            $block->currentLi->content[] = '';
            $text = preg_replace('/^[ ]{0,4}/', '', $line->body);
            $block->currentLi->content[] = $text;
            $block->isInterrupted = false;

            return $block;
        }
    }


// Markup
    protected function _beginMarkupBlock(Parser_Line $line, Parser_Block $block=null) {
        if($this->_escapeMarkup) {
            return;
        }

        if(!preg_match('/^<(\w*)(?:[ ]*'.self::ATTRIBUTE_REGEX.')*[ ]*(\/)?>/', $line->text, $matches)) {
            return;
        }

        if(aura\html\Tag::isInlineTagName($matches[1])) {
            return;
        }

        $block = new Parser_Block([
            'name' => $matches[1],
            'depth' => 0,
            'markup' => $line->text,
            'isClosed' => false
        ]);

        $length = strlen($matches[0]);
        $remainder = substr($line->text, $length);

        if(trim($remainder) === '') {
            if(isset($matches[2]) || aura\html\Tag::isClosableTagName($matches[1])) {
                $block->isClosed = true;
                $block->isVoid = true;
            }
        } else {
            if(isset($matches[2]) || aura\html\Tag::isClosableTagName($matches[1])) {
                return;
            }

            if(preg_match('/<\/'.$matches[1].'>[ ]*$/i', $remainder)) {
                $block->isClosed = true;
            }
        }

        return $block;
    }

    protected function _continueMarkupBlock(Parser_Line $line, Parser_Block $block) {
        if($block->isClosed) {
            return;
        }

        if(preg_match('/^<'.$block->name.'(?:[ ]*'.self::ATTRIBUTE_REGEX.')*[ ]*>/i', $line->text)) {
            $block->depth++;
        }

        if(preg_match('/(.*?)<\/'.$block->name.'>[ ]*$/i', $line->text, $matches)) {
            if($block->depth > 0) {
                $block--;
            } else {
                $block->isClosed = true;
            }
        }

        if($block->isInterrupted) {
            $block->markup .= "\n";
            $block->isInterrupted = false;
        }

        $block->markup .= "\n".$line->body;
        return $block;
    }


// Paragraph
    protected function _beginParagraphBlock(Parser_Line $line, Parser_Block $block=null) {
        return new Parser_Block([
            'element' => new Parser_Element([
                'name' => 'p',
                'content' => $line->text,
                'handler' => 'line'
            ])
        ]);
    }


// Quote
    protected function _beginQuoteBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!preg_match('/^>[ ]?(.*)/', $line->text, $matches)) {
            return;
        }

        return new Parser_Block([
            'element' => new Parser_Element([
                'name' => 'blockquote',
                'handler' => 'lines',
                'content' => [$matches[1]]
            ])
        ]);
    }

    protected function _continueQuoteBlock(Parser_Line $line, Parser_Block $block) {
        if($line->text{0} === '>' && preg_match('/^>[ ]?(.*)/', $line->text, $matches)) {
            if($block->isInterrupted) {
                $block->element->content[] = '';
                $block->isInterrupted = false;
            }

            $block->element->content[] = $matches[1];
            return $block;
        }

        if(!$block->isInterrupted) {
            $block->element->content[] = $line->text;
            return $block;
        }
    }



// Reference
    protected function _beginReferenceBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $line->text, $matches)) {
            return;
        }

        $id = strtolower($matches[1]);

        $this->_definitions['Reference'][$id] = new Parser_Definition([
            'url' => $matches[2],
            'title' => isset($matches[3]) ? $matches[3] : null
        ]);

        $block->isHidden = true;
        return $block;
    }



// Rule
    protected function _beginRuleBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!preg_match('/^(['.$line->text{0}.'])([ ]*\1){2,}[ ]*$/', $line->text)) {
            return;
        }

        return new Parser_Block([
            'element' => new Parser_Element([
                'name' => 'hr'
            ])
        ]);
    }


// Setext header
    protected function _beginSetextHeaderBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!$block || $block->type || $block->isInterrupted) {
            return;
        }

        if(rtrim($line->text, $line->text{0}) === '') {
            $block->element->name = $line->text{0} === '=' ? 'h1' : 'h2';
            return $block;
        }
    }



// Table
    protected function _beginTableBlock(Parser_Line $line, Parser_Block $block=null) {
        if(!$block || $block->type || $block->isInterrupted) {
            return;
        }

        if(false === strpos($block->element->content, '|')
        || rtrim($line->text, ' -:|') === '') {
            return;
        }

        $alignments = [];
        $divider = trim(trim($line->text), '|');
        $cells = explode('|', $divider);

        foreach($cells as $cell) {
            $cell = trim($cell);

            if($cell === '') {
                continue;
            }

            $alignment = null;

            if($cell{0} === ':') {
                $alignment = 'left';
            }

            if(substr($cell, -1) === ':') {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }

            $alignments[] = $alignment;
        }

        $headerElements = [];
        $header = trim(trim($block->element->content), '|');
        $cells = explode('|', $header);

        foreach($cells as $index => $cell) {
            $cell = trim($cell);

            $element = new Parser_Element([
                'name' => 'th',
                'content' => $cell,
                'handler' => 'line'
            ]);

            if(isset($alignments[$index])) {
                $alignment = $alignments[$index];
                $element->attributes = [
                    'style' => 'text-align: '.$alignment
                ];
            }

            $headerElements[] = $element;
        }


        return new Parser_Block([
            'alignments' => $alignments,
            'isIdentified' => true,
            'element' => new Parser_Element([
                'name' => 'table',
                'handler' => 'elements',
                'content' => [
                    'head' => new Parser_Element([
                        'name' => 'thead',
                        'handler' => 'elements',
                        'content' => [
                            new Parser_Element([
                                'name' => 'tr',
                                'handler' => 'elements',
                                'content' => $headerElements
                            ])
                        ]
                    ]),

                    'body' => new Parser_Element([
                        'name' => 'tbody',
                        'handler' => 'elements',
                        'content' => []
                    ])
                ]
            ])
        ]);
    }

    protected function _continueTableBlock(Parser_Line $line, Parser_Block $block) {
        if($block->isInterrupted
        || !($line->text{0} === '|' || strpos($line->text, '|'))) {
            return;
        }

        $elements = [];
        $row = trim(trim($line->text), '|');

        preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/', $row, $matches);

        foreach($matches[0] as $index => $cell) {
            $cell = trim($cell);

            $element = new Parser_Element([
                'name' => 'td',
                'handler' => 'line',
                'content' => $cell
            ]);

            if(isset($block->alignments[$index])) {
                $element->attributes = [
                    'style' => 'text-align: '.$block->alignments[$index].';'
                ];
            }

            $elements[] = $element;
        }

        $block->element->content['body']->content[] = new Parser_Element([
            'name' => 'tr',
            'handler' => 'elements',
            'content' => $elements
        ]);

        return $block;
    }




## Handlers


// Element
    protected function _handleElement(Parser_Element $element) {
        if($element->content) {
            if($element->handler) {
                $content = new aura\html\ElementString(
                    $this->{'_handle'.ucfirst($element->handler)}($element->content)
                );
            } else {
                $content = $element->content;
            }
        } else {
            $content = null;
        }

        $output = new aura\html\Element($element->name, $content, $element->attributes);

        return (string)$output;
    }


// Elements
    protected function _handleElements(array $elements) {
        $markup = '';

        foreach($elements as $element) {
            $markup .= "\n".$this->_handleElement($element);
        }

        $markup .= "\n";
        return $markup;
    }


// Line
    protected function _handleLine($text) {
        $markup = '';

        while($excerpt = strpbrk($text, self::INLINE_MARKERS)) {
            $marker = $excerpt{0};
            $markerPosition = strpos($text, $marker);

            $excerpt = new Parser_Excerpt([
                'marker' => $marker,
                'text' => $excerpt,
                'context' => $text
            ]);

            foreach(self::$_inlineTypes[$marker] as $inlineType) {
                $inline = $this->{'_parse'.$inlineType}($excerpt);

                if(!$inline) {
                    continue;
                }

                if($inline->position !== null) {
                    if($inline->position > $markerPosition) {
                        continue;
                    }
                } else {
                    $inline->position = $markerPosition;
                }

                $unmarkedText = substr($text, 0, $inline->position);
                $markup .= $this->_normalizeUnmarkedText($unmarkedText);
                $markup .= isset($inline->markup) ?
                    $inline->markup :
                    $this->_handleElement($inline->element);

                $text = substr($text, $inline->position + $inline->extent);
                continue 2;
            }

            $unmarkedText = substr($text, 0, $markerPosition + 1);
            $markup .= $this->_normalizeUnmarkedText($unmarkedText);
            $text = substr($text, $markerPosition + 1);
        }

        $markup .= $this->_normalizeUnmarkedText($text);
        return $markup;
    }


// Lines
    protected function _handleLines($lines) {
        $markup = '';

        foreach($this->_scanLines($lines) as $block) {
            if($block->isHidden) {
                continue;
            }

            $markup .= "\n";
            $markup .= isset($block->markup) ?
                $block->markup :
                $this->_handleElement($block->element);
        }

        $markup .= "\n";
        return $markup;
    }


// List item
    protected function _handleListItem($content) {
        if(!is_array($content)) {
            $content = [$content];
        }

        $markup = $this->_handleLines($content);
        $trimmed = trim($markup);

        if(!in_array('', $content)
        && substr($trimmed, 0, 3) == '<p>') {
            $markup = substr($trimmed, 3);
            $position = strpos($markup, '</p>');
            $markup = substr_replace($markup, '', $position, 4);
        }

        return $markup;
    }





## Inline parsers


// Special char
    protected function _parseCode(Parser_Excerpt $excerpt) {
        if(!preg_match('/^('.$excerpt->marker.'+)[ ]*(.+?)[ ]*(?<!'.$excerpt->marker.')\1(?!'.$excerpt->marker.')/s', $excerpt->text, $matches)) {
            return;
        }

        $text = $matches[2];
        $text = preg_replace("/[ ]*\n/", ' ', $text);

        return new Parser_Inline([
            'extent' => strlen($matches[0]),
            'element' => new Parser_Element([
                'name' => 'code',
                'content' => $text
            ])
        ]);
    }

    protected function _parseEmailTag(Parser_Excerpt $excerpt) {
        if(false === strpos($excerpt->text, '>')
        || !preg_match('/^<((mailto:)?\S+?@\S+?)>/i', $excerpt->text, $matches)) {
            return;
        }

        $url = $matches[1];

        if(!isset($matches[2])) {
            $url = 'mailto:'.$url;
        }

        return new Parser_Inline([
            'extent' => strlen($matches[0]),
            'element' => new Parser_Element([
                'name' => 'a',
                'content' => $matches[1],
                'attributes' => [
                    'href' => $url
                ]
            ])
        ]);
    }

    protected function _parseEmphasis(Parser_Excerpt $excerpt) {
        if(!isset($excerpt->text{1})) {
            return;
        }

        if($excerpt->text{1} === $excerpt->marker
        && preg_match(self::$_strongRegex[$excerpt->marker], $excerpt->text, $matches)) {
            $name = 'strong';
        } else if(preg_match(self::$_emRegex[$excerpt->marker], $excerpt->text, $matches)) {
            $name = 'em';
        } else {
            return;
        }

        return new Parser_Inline([
            'extent' => strlen($matches[0]),
            'element' => new Parser_Element([
                'name' => $name,
                'handler' => 'line',
                'content' => $matches[1]
            ])
        ]);
    }

    protected function _parseEscapeSequence(Parser_Excerpt $excerpt) {
        if(!isset($excerpt->text{1}) || false === strpos(self::SPECIAL_CHARACTERS, $excerpt->text{1})) {
            return;
        }

        return new Parser_Inline([
            'extent' => 2,
            'markup' => $excerpt->text{1}
        ]);
    }

    protected function _parseImage(Parser_Excerpt $excerpt) {
        if(!isset($excerpt->text{1}) || $excerpt->text{1} !== '[') {
            return;
        }

        $origText = $excerpt->text;
        $excerpt->text = substr($excerpt->text, 1);
        $link = $this->_parseLink($excerpt);

        if(!$link) {
            $excerpt->text = $origText;
            return;
        }

        $output = new Parser_Inline([
            'extent' => $link->extent + 1,
            'element' => new Parser_Element([
                'name' => 'img',
                'attributes' => [
                    'src' => $link->element->attributes['href'],
                    'alt' => $link->element->content
                ]
            ])
        ]);

        $output->element->attributes = array_merge(
            $output->element->attributes,
            $link->element->attributes
        );

        unset($output->element->attributes['href']);
        return $output;
    }

    protected function _parseLink(Parser_Excerpt $excerpt) {
        $element = new Parser_Element([
            'name' => 'a',
            'handler' => 'line',
            'attributes' => [
                'href' => null,
                'title' => null
            ]
        ]);

        $extent = 0;
        $remainder = $excerpt->text;

        if(!preg_match('/\[((?:[^][]|(?R))*)\]/', $remainder, $matches)) {
            return;
        }

        $element->content = $matches[1];
        $extent += strlen($matches[0]);
        $remainder = substr($remainder, $extent);

        if(preg_match('/^[(]((?:[^ ()]|[(][^ )]+[)])+)(?:[ ]+("[^"]*"|\'[^\']*\'))?[)]/', $remainder, $matches)) {
            $element->attributes['href'] = $this->_normalizeUrl($matches[1]);

            if(isset($matches[2])) {
                $element->attributes->title = substr($matches[2], 1, -1);
            }

            $extent += strlen($matches[0]);
        } else {
            if(preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
                $definition = strlen($matches[1]) ? $matches[1] : $element->content;
                $definition = strtolower($definition);

                $extent += strlen($matches[0]);
            } else {
                $definition = strtolower($element->content);
            }

            if(!isset($this->_definitions['Reference'][$definition])) {
                return;
            }

            $definition = $this->_definitions['Reference'][$definition];
            $element->attributes['href'] = $definition->url;
            $element->attributes['title'] = $definition->title;
        }

        return new Parser_Inline([
            'extent' => $extent,
            'element' => $element
        ]);
    }

    protected function _parseMarkup(Parser_Excerpt $excerpt) {
        if($this->_escapeMarkup || false === strpos($excerpt->text, '>')) {
            return;
        }

        if(($excerpt->text{1} == '/' && preg_match('/^<\/\w*[ ]*>/s', $excerpt->text, $matches))
        || ($excerpt->text{1} == '!' && preg_match('/^<!---?[^>-](?:-?[^-])*-->/s', $excerpt->text, $matches))
        || ($excerpt->text{1} == ' ' && preg_match('/^<\w*(?:[ ]*'.self::ATTRIBUTE_REGEX.')*[ ]*\/?>/s', $excerpt->text, $matches))) {
            return new Parser_Inline([
                'markup' => $matches[0],
                'extent' => strlen($matches[0])
            ]);
        }
    }

    protected function _parseSpecialCharacter(Parser_Excerpt $excerpt) {
        if($excerpt->text{0} == '&'
        && preg_match('/^&#?\w+;/', $excerpt->text)) {
            return new Parser_Inline([
                'markup' => $excerpt->text,
                'extent' => 1
            ]);
        }

        switch($excerpt->text{0}) {
            case '>': $entity = 'gt'; break;
            case '<': $entity = 'lt'; break;
            case '"': $entity = 'quot'; break;
            default:
                return;
        }

        return new Parser_Inline([
            'markup' => '&'.$entity.';',
            'extent' => 1
        ]);
    }

    protected function _parseStrikethrough(Parser_Excerpt $excerpt) {
        if(!isset($excerpt->text{1})) {
            return;
        }

        if($excerpt->text{1} != '~'
        || !preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $excerpt->text, $matches)) {
            return;
        }

        return new Parser_Inline([
            'extent' => strlen($matches[0]),
            'element' => new Parser_Element([
                'name' => 'del',
                'content' => $matches[1],
                'handler' => 'line'
            ])
        ]);
    }

    protected function _parseUrl(Parser_Excerpt $excerpt) {
        if(!isset($excerpt->text{2}) || $excerpt->text{2} !== '/') {
            return;
        }

        if(!preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $excerpt->context, $matches)) {
            return;
        }

        return new Parser_Inline([
            'extent' => strlen($matches[0][0]),
            'position' => $matches[0][1],
            'element' => new Parser_Element([
                'name' => 'a',
                'content' => $matches[0][0],
                'attributes' => [
                    'href' => $this->_normalizeUrl($matches[0][0])
                ]
            ])
        ]);
    }

    protected function _parseUrlTag(Parser_Excerpt $excerpt) {
        if(false === strpos($excerpt->text, '>')
        || !preg_match('/^<(\w+:\/{2}[^ >]+)>/i', $excerpt->text, $matches)) {
            return;
        }

        return new Parser_Inline([
            'extent' => strlen($matches[0]),
            'element' => new Parser_Element([
                'name' => 'a',
                'content' => $this->_normalizeUrl($matches[1])
            ])
        ]);
    }



## Helpers
    protected function _normalizeUnmarkedText($text) {
        $text = preg_replace('/(?:[ ][ ]+|[ ]*\\\\)\n/', "<br />\n", $text);
        $text = str_replace(" \n", "\n", $text);

        return $text;
    }

    protected function _normalizeUrl($url) {
        return $this->_context->uri->__invoke($url);
    }
}



class Parser_Line extends core\lang\Struct {
    public $indent = 0;
    public $body;
    public $text;
}

class Parser_Block extends core\lang\Struct {
    public $type;

    public $isInterrupted = false;
    public $isComplete = false;
    public $isIdentified = false;
    public $isHidden = false;

    public $element;
    public $markup;
    public $indent = 0;
}

class Parser_Inline extends core\lang\Struct {
    public $extent;
    public $element;
    public $markup;
    public $position;
}

class Parser_Element extends core\lang\Struct {
    public $name;
    public $handler;
    public $type;
    public $content;
    public $attributes;
}

class Parser_Excerpt extends core\lang\Struct {
    public $marker;
    public $text;
    public $context;
}

class Parser_Definition extends core\lang\Struct {
    public $url;
    public $title;
}