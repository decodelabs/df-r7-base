<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\package;

use df;
use df\core;
use df\flex;
use df\iris;
    
class Foundation extends Base {

    protected static $_environments = [
        'root', 'center', 'document', 'enumerate', 'equation', 'eqnarray', 'figure', 
        'multline', 'table', 'tabular', 'thebibliography'
    ];

    protected static $_commands = [
        '@', '\\', ',', ';', ':', '!', '-', '>', '<', '+', '|', '(', ')', '[', ']',

        'addcontentsline', 'addtocontents', 'addtocounter', 'address', 'addtolength', 'addvspace', 'alph',
        'appendix', 'arabic', 'author', 'backslash', 'baselineskip', 'baselinestretch', 'bf', 
        'bibitem', 'bigskipamount', 'bigskip', 'boldmath', 'cal', 'caption', 'cdots', 'centering', 'chapter', 
        'circle', 'cite', 'cleardoublepage', 'clearpage', 'closing', 'copyright', 'dashbox', 'date',
        'ddots', 'documentclass', 'dotfill', 'em', 'emph', 'ensuremath', 'fbox', 'flushbottom', 'fnsymbol',
        'footnote', 'footnotemark', 'footnotesize', 'footnotetext', 'frac', 'frame', 'framebox', 'frenchspacing',
        'hline', 'hfill', 'hrulefill', 'hspace', 'huge', 'Huge', 'hyphenation', 'include', 'includeonly', 'indent', 
        'input', 'it', 'item', 'kill', 'label', 'large', 'Large', 'LARGE', 'LaTeX', 'LaTeXe', 'ldots', 'left',
        'lefteqn', 'line', 'linebreak', 'linethickness', 'linewidth', 'listoffigures', 'listoftables', 
        'location', 'makeatletter', 'makeatother', 'makebox', 'maketitle', 'markboth', 'markright', 'mathcal', 
        'mathop', 'mbox', 'medskip', 'multicolumn', 'multiput', 'newcommand', 'newcounter', 'newenvironment', 
        'newfont', 'newlength', 'newline', 'newpage', 'newsavebox', 'newtheorem', 'nocite', 'noindent', 
        'nolinebreak', 'nonfrenchspacing', 'normalsize', 'nopagebreak', 'not', 'onecolumn', 'opening', 'oval', 
        'overbrace', 'overline', 'pagebreak', 'pagenumbering', 'pageref', 'pagestyle', 'par', 'paragraph', 'parbox',
        'parindent', 'parskip', 'part', 'protect', 'providecommand', 'put', 'raggedbottom', 'raggedleft',
        'raggedright', 'raisebox', 'ref', 'renewcommand', 'right', 'rm', 'roman', 'rule', 'savebox', 'sbox',
        'sc', 'scriptsize', 'section', 'setcounter', 'setlength', 'settowidth', 'sf', 'shortstack', 
        'signature', 'sl', 'slash', 'small', 'smallskip', 'sout', 'space', 'sqrt', 'stackrel', 'stepcounter',
        'subparagraph', 'subsection', 'subsubsection', 'tableofcontents', 'telephone', 'TeX', 'textbf',
        'textit', 'textmd', 'textnormal', 'textrm', 'textsc', 'textsf', 'textsl', 'texttt', 'textup', 
        'textwidth', 'textheight', 'thanks', 'thispagestyle', 'tiny', 'title', 'today', 'tt', 'twocolumn',
        'typeout', 'typein', 'uline', 'underbrace', 'underline', 'unitlength', 'usebox', 'usecounter', 
        'usepackage', 'uwave', 'value', 'vbox', 'vcenter', 'vdots', 'vector', 'verb', 'vfill', 'vline', 
        'vphantom', 'vspace'
    ];





// Root
    public function environment_root() {
        while(true) {
            $token = $this->parser->extract();

            if(!$token || $token->matches('eof')) {
                break;
            }

            if($token->matches('command')) {
                $this->parser->parseCommand($token->value);
            } else {
                throw new iris\UnexpectedTokenException(
                    'Expected command', $token
                );
            }
        }
    }   


// Center
    public function environment_center() {
        $block = new flex\latex\map\Block($this->parser->token);
        $block->isInline(false);
        $block->setType('align');
        $block->setAttribute('align', 'center');

        return $this->parser->parseStandardContent($block);
    }

// Document
    public function environment_document() {
        return $this->parser->parseStandardContent($this->parser->document);
    }


// Enumerate
    public function environment_enumerate() {
        $list = new flex\latex\map\Structure($this->parser->token);
        $list->setType('orderedList');
        $this->parser->pushContainer($list);

        while(!$this->parser->token->matches('command', null, 'end')) {
            $item = new flex\latex\map\Block($this->parser->token);
            $item->setType('listItem');

            $this->parser->extractMatch('command', null, 'item');

            $this->parser->parseStandardContent($item, ['item', 'end']);
        }

        $this->parser->popContainer();
        $this->parser->extractMatch('command', null, 'end');
        $this->parser->parseCommand('end');

        return $list;
    }


// Equation
    public function environment_equation() {
        $output = $this->parser->parseBlockMathMode('equation');

        $this->parser->extractMatch('command', null, 'end');
        $this->parser->parseCommand('end');

        return $output;
    }

    public function environment_eqnarray() {
        $output = $this->parser->parseBlockMathMode('eqnarray');

        $this->parser->extractMatch('command', null, 'end');
        $this->parser->parseCommand('end');
        
        return $output;
    }

    public function environment_multline() {
        $output = $this->parser->parseBlockMathMode('multline');

        $this->parser->extractMatch('command', null, 'end');
        $this->parser->parseCommand('end');
        
        return $output;
    }

// Figure
    protected $_figureCounter = 0;

    public function environment_figure() {
        $figure = new flex\latex\map\Figure($this->parser->token);

        if($this->parser->lastComment
        && preg_match('/[fF]igure ([0-9]+)/', $this->parser->lastComment->value, $matches)) {
            $figure->setNumber($this->_figureCounter = (int)$matches[1]);
        } else {
            $figure->setNumber(++$this->_figureCounter);
        }

        $options = array_keys($this->parser->extractOptionList());
        $figure->setPlacement(array_shift($options));
        
        $this->parser->parseStandardContent($figure);
        
        return $figure;
    }


// Footnotesize
    public function command_footnotesize() {
        if($this->parser->getLastToken()->isWhitespaceSingleNewLine()) {
            $this->parser->writeToTextNode(' ');
        }

        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('small');
        $block->isInline(true);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true);
        $this->parser->extractValue('}');

        return $block;
    }


// Table
    protected $_tableCounter = 0;

    public function environment_table() {
        $table = new flex\latex\map\Table($this->parser->token);
        $options = array_keys($this->parser->extractOptionList());
        $table->setPlacement(array_shift($options));
        $table->setNumber(++$this->_tableCounter);

        $this->parser->parseStandardContent($table);

        return $table;
    }

// Tabular
    public function environment_tabular() {
        $pop = false;

        if(!$this->parser->container instanceof flex\latex\ITabular) {
            $table = new flex\latex\map\Table($this->parser->token);
            $table->setNumber(++$this->_tableCounter);
            $this->parser->pushContainer($table);
            $pop = true;
        }

        // Parse tabbing declaration
        $this->parser->extractValue('{');
        
        while(!$this->parser->token->isValue('}')) {
            $token = $this->parser->extract();
            $col = new flex\latex\map\Column($token);

            if($token->isValue('|')) {
                $col->hasLeftBorder(true);

                $token = $this->parser->extract();

                if($token->isValue('|')) {
                    $col->hasLeftBorder(2);
                    $token = $this->parser->extract();
                }
            }

            if($token->isValue('>', '<')) {
                throw new iris\UnexpectedTokenException(
                    'Need to implement column format parsing',
                    $token
                );
            }

            if(!$token->is('word')) {
                throw new iris\UnexpectedTokenException(
                    'Expected column definition',
                    $token
                );
            }

            switch($token->value) {
                case 'l':
                case 'c':
                case 'r':
                    $col->setAlignment($token->value);
                    break;

                case 'p':
                case 'm':
                case 'b':
                    $col->setAlignment($token->value);

                    $this->parser->extractValue('{');
                    $col->setParagraphSizing($this->parser->extractRefId());
                    $this->parser->extractValue('}');
                    break;

                default:
                    throw new iris\UnexpectedTokenException(
                        'I don\'t know what to do with this table definition',
                        $token
                    );
            }

            if($this->parser->token->isValue('|')) {
                $b = 1;

                if($this->parser->peek(1)->value == '|') {
                    $b++;
                    $this->parser->extract();
                }

                if($this->parser->peek(1)->value == '}') {
                    $this->parser->extract();
                }

                $col->hasRightBorder($b);
            }

            $this->parser->container->addColumn($col);
        }

        $this->parser->extractValue('}');


        // Parse rows
        $row = array();

        while(!$this->parser->token->is('command=end')) {
            $block = new flex\latex\map\Block($this->parser->token);
            $block->setType('cell');

            if($this->parser->token->is('keySymbol=~')) {
                $this->parser->extract();
            } else if($this->parser->token->is('keySymbol=&')) {
                $this->parser->extract();
                continue;
            } else if($this->parser->token->is('command=\\')) {
                $this->parser->extract();

                if(!empty($row)) {
                    $this->parser->container->addRow($row);
                }

                $row = array();
                continue;
            } else if($this->parser->token->is('command=hline')) {
                $this->parser->extract();
                $this->parser->parseCommand('hline');
                continue;
            } else {
                $this->parser->parseStandardContent($block, ['&', '\\'], false);

            }

            $row[] = $block;
        }

        if(!empty($row)) {
            $this->parser->container->addRow($row);
        }

        if($pop) {
            $this->parser->popContainer();
        }

        $this->parser->extractMatch('command', null, 'end');
        $this->parser->parseCommand('end');
    }


// Thebibiliography
    public function environment_thebibliography() {
        $bibliography = new flex\latex\map\Bibliography($this->parser->token);
        $this->parser->pushContainer($bibliography);

        $this->parser->extractValue('{');
        $digits = $this->parser->extractWord();
        $this->parser->extractValue('}');

        $bibliography->setDigitLength(strlen($digits->value));

        while(!$this->parser->token->matches('command', null, 'end')) {
            $item = new flex\latex\map\Block($this->parser->token);
            $item->setType('bibitem');

            $this->parser->extractMatch('command', null, 'bibitem');
            $this->parser->extractValue('{');
            $id = $this->parser->extractRefId();
            $this->parser->extractValue('}');

            $item->setId($id);
            $this->parser->parseStandardContent($item, ['bibitem', 'end']);
        }

        $this->parser->popContainer();
        $this->parser->extractMatch('command', null, 'end');
        $this->parser->parseCommand('end');

        return $bibliography;
    }



// Symbol
    public function command_callSymbol($symbol, $isStar) {
        switch($symbol) {
            case '\\':
                if($this->parser->container && !$this->parser->container->isEmpty()) {
                    $this->parser->writeToTextNode("\n");
                }

                break;

            /*
            case '`':
            case '\'':
            case '^':
            case '"':
            case '~':
            case '=':
            case '.':
                $this->parser->extractCharacterSymbol($symbol);
                break;
            */

            default:
                core\dump($symbol, $isStar);
        }
    }


// Author
    public function command_author() {
        $this->parser->extractValue('{');
        $author = '';

        while($token = $this->parser->extractIfMatch('word')) {
            $author .= ' '.$token->value;
        }

        $author = trim($author);
        $this->parser->document->setTitle($author);
        $this->parser->extractValue('}');
    }



// Caption
    public function command_caption() {
        $caption = new flex\latex\map\Block($this->parser->token);
        $caption->setType('caption');

        if(!$this->parser->container instanceof flex\latex\ICaptioned) {
            $this->rewind();

            throw new iris\UnexpectedTokenException(
                'Container is not captioned',
                $this->parser->token
            );
        }

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($caption, true, false);
        $this->parser->extractValue('}');

        $this->parser->container->setCaption($caption);

        return $caption;
    }


// Cite
    public function command_cite() {
        $reference = new flex\latex\map\Reference($this->parser->token);
        $reference->setType('cite');

        $this->parser->extractValue('{');
        $id = $this->parser->extractRefId();
        $this->parser->extractValue('}');

        $reference->setId($id);
        $this->parser->container->push($reference);

        return $reference;
    }


// Date
    public function command_date() {
        $this->parser->extractValue('{');

        if($this->parser->token->is('command=today')) {
            $this->parser->document->setDate('now');
            $this->parser->extract();
        } else {
            $date = '';

            while(!$this->parser->token->isValue('}')) {
                $token = $this->parser->extract();

                if($token->isAfterWhitespace()) {
                    $date .= ' ';
                }

                $date .= $token->value;
            }

            $this->parser->document->setDate(trim($date));
        }

        $this->parser->extractValue('}');
    }

// Document class
    public function command_documentclass() {
        // Options
        if($this->parser->extractIfValue('[')) {
            while(true) {
                $word = $this->parser->extractWord();
                $this->parser->document->addOption($word->value);

                if($this->parser->extractIfValue(']')) {
                    break;
                } else {
                    $this->parser->extractValue(',');
                }
            }
        }

        // Class
        $this->parser->extractValue('{');

        $this->parser->document->setDocumentClass(
            $this->parser->extractWord()->value
        );

        $this->parser->extractValue('}');
    }


// Hline
    public function command_hline() {
        $this->parser->container->push(
            $output =(new flex\latex\map\Block($this->parser->token))
                ->setType('hline')
        );

        return $output;
    }


// Label
    public function command_label() {
        $this->parser->extractValue('{');
        $token = $this->parser->token;
        $id = $this->parser->extractRefId();
        $this->parser->extractValue('}');

        $stack = $this->parser->getContainerStack();

        foreach(array_reverse($stack) as $container) {
            if($container instanceof flex\latex\IReferable
            && !$container instanceof flex\latex\map\Block) {
                if($container->getId()) {
                    throw new iris\UnexpectedTokenException(
                        'Trying to set id on container that already has an id',
                        $token
                    );
                }

                $container->setId($id);
                break;
            }
        }

        return $id;
    }

// Makeatletter
    public function command_makeatletter() {
        while(!$this->parser->token->is('command=makeatother')) {
            $this->parser->extract();
        }
    }

// Makeatother
    public function command_makeatother() {
        // do nothing
    }


// Makebox
    public function command_makebox() {
        return $this->parser->skipCommand();
    }


// Multicolumn
    public function command_multicolumn() {
        if(!$this->parser->container instanceof flex\latex\IGenericBlock
        || $this->parser->container->getType() != 'cell') {
            throw new iris\UnexpectedTokenException(
                'Not in a cell', $this->parser->token
            );
        }

        $this->parser->extractValue('{');
        $cols = $this->parser->extractWord();
        $this->parser->container->setAttribute('colspan', (int)$cols->value);
        $this->parser->extractValue('}');

        $this->parser->extractValue('{');
        $align = $this->parser->extractWord();

        switch($align->value) {
            case 'l':
                $align = 'left';
                break;

            case 'r':
                $align = 'right';
                break;

            case 'c':
                $align = 'center';
                break;

            default:
                $align = $align->value;
                break;
        }

        $this->parser->container->setAttribute('align', $align);
        $this->parser->extractValue('}');

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($this->parser->container, true, false);
        $this->parser->extractValue('}');

        return $this->parser->container;
    }


// Noindent
    public function command_noindent() {
        return $this->parser->skipCommand(false);
    }



// Ref
    public function command_ref() {
        $reference = new flex\latex\map\Reference($this->parser->token);
        $reference->setType('ref');

        $this->parser->extractValue('{');
        $id = $this->parser->extractRefId();
        $this->parser->extractValue('}');

        $reference->setId($id);
        $this->parser->container->push($reference);

        return $reference;
    }


// Section
    protected $_sectionCounter = 0;

    public function command_section($isStar) {
        $this->parser->closeParagraph();

        $section = new flex\latex\map\Block($this->parser->token);
        $section->setType('section');
        $section->setAttribute('level', 1);
        $section->setNumber(++$this->_sectionCounter);
        $section->setAttribute('renderNumber', !$isStar);

        $this->_subsectionCounter = 0;

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($section, true);
        $this->parser->extractValue('}');

        return $section;
    }


// Small
    public function command_small() {
        if($this->parser->getLastToken()->isWhitespaceSingleNewLine()) {
            $this->parser->writeToTextNode(' ');
        }

        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('small');
        $block->isInline(true);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true);
        $this->parser->extractValue('}');

        return $block;
    }


// Subsection
    protected $_subsectionCounter = 0;

    public function command_subsection($isStar) {
        $this->parser->closeParagraph();
        
        $section = new flex\latex\map\Block($this->parser->token);
        $section->setType('section');
        $section->setAttribute('level', 2);
        $section->setNumber(++$this->_subsectionCounter);
        $section->setAttribute('renderNumber', !$isStar);

        $this->_subsubsectionCounter = 0;

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($section, true);
        $this->parser->extractValue('}');

        return $section;
    }


// Subsubsection
    protected $_subsubsectionCounter = 0;

    public function command_subsubsection($isStar) {
        $this->parser->closeParagraph();
        
        if($this->parser->container instanceof flex\latex\IGenericBlock
        && $this->parser->container->getType() == 'bibitem') {
            return $this->_bibitemSubsection();
        }

        $section = new flex\latex\map\Block($this->parser->token);
        $section->setType('section');
        $section->setAttribute('level', 3);
        $section->setNumber(++$this->_subsubsectionCounter);
        $section->setAttribute('renderNumber', !$isStar);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($section, true);
        $this->parser->extractValue('}');

        return $section;
    }

    protected function _bibitemSubsection() {
        $this->parser->popContainer();
        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('subheading');

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true, false);
        $this->parser->extractValue('}');

        $this->parser->pushContainer($block);

        return $block;
    }


// Textit
    public function command_textit() {
        if($this->parser->getLastToken()->isWhitespaceSingleNewLine()) {
            $this->parser->writeToTextNode(' ');
        }

        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('italic');
        $block->isInline(true);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true);
        $this->parser->extractValue('}');

        return $block;
    }

// Textwidth
    public function command_textwidth() {
        return (new flex\latex\map\Macro($this->parser->token))->setName('textwidth');
    }


// Tiny
    public function command_tiny() {
        if($this->parser->getLastToken()->isWhitespaceSingleNewLine()) {
            $this->parser->writeToTextNode(' ');
        }

        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('tiny');
        $block->isInline(true);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true);
        $this->parser->extractValue('}');

        return $block;
    }


// Title
    public function command_title() {
        $this->parser->extractValue('{');
        $title = '';

        while($token = $this->parser->extractIfMatch('word')) {
            $title .= ' '.$token->value;
        }

        $title = trim($title);
        $this->parser->document->setTitle($title);
        $this->parser->extractValue('}');
    }


// Use package
    public function command_usepackage() {
        // Options
        $options = $this->parser->extractOptionList();

        // Names
        $this->parser->extractValue('{');

        while(true) {
            $word = $this->parser->extractWord();
            $this->parser->document->addPackage($word->value, $options);

            $class = 'df\\flex\\latex\\package\\'.ucfirst($word->value);

            if(class_exists($class)) {
                $this->parser->addProcessor(new $class());
            }

            if($this->parser->extractIfValue('}')) {
                break;
            } else {
                $this->parser->extractValue(',');
            }
        }
    }


// Vspace
    public function command_vspace() {
        $this->parser->skipCommand();
    }
}