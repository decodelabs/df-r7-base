<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\package;

use df;
use df\core as coreLib;
use df\flex;
use df\iris;
    
class Core extends Base {

    protected static $_environments = ['root', 'document'];

    protected static $_commands = [
        '@', '\\', ',', ';', ':', '!', '-', '=', '>', '<', '+', '\'', '`', '|', '(', ')', '[', ']',

        'addcontentsline', 'addtocontents', 'addtocounter', 'address', 'addtolength', 'addvspace', 'alph',
        'appendix', 'arabic', 'author', 'backslash', 'baselineskip', 'baselinestretch', 'begin', 'bf', 
        'bibitem', 'bigskipamount', 'bigskip', 'boldmath', 'cal', 'caption', 'cdots', 'centering', 'chapter', 
        'circle', 'cite', 'cleardoublepage', 'clearpage', 'closing', 'copyright', 'dashbox', 'date',
        'ddots', 'documentclass', 'dotfill', 'em', 'emph', 'end', 'ensuremath', 'fbox', 'flushbottom', 'fnsymbol',
        'footnote', 'footnotemark', 'footnotesize', 'footnotetext', 'frac', 'frame', 'framebox', 'frenchspacing',
        'hfill', 'hrulefill', 'hspace', 'huge', 'Huge', 'hyphenation', 'include', 'includeonly', 'indent', 
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

// Document
    public function environment_document() {
        return $this->parser->parseStandardContent($this->parser->document);
    }



// Symbol
    public function command_callSymbol($symbol, $isStar) {
        switch($symbol) {
            case '\\':
                if($this->parser->container instanceof flex\latex\IParagraph
                && !$this->parser->container->isEmpty()) {
                    $this->parser->container->push(
                        (new flex\latex\map\TextNode($this->parser->token->getLocation()))
                            ->setText("\n")
                    );
                }

                break;

            default:
                coreLib\dump($symbol, $isStar);
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

// Begin
    public function command_begin() {
        $this->parser->extractValue('{');
        $envName = $this->parser->extractMatch('word')->value;
        $this->parser->extractValue('}');

        return $this->parser->parseEnvironment($envName);
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
                $word = $this->parser->extractMatch('word');
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
            $this->parser->extractMatch('word')->value
        );

        $this->parser->extractValue('}');
    }


// End
    public function command_end() {
        $this->parser->extractValue('{');
        $env = $this->parser->extractMatch('word');
        $this->parser->extractValue('}');

        if($env->value != $this->parser->environment) {
            throw new iris\UnexpectedTokenException(
                'Found end of '.$env->value.' environment, expected end of '.$this->parser->environment,
                $env
            );
        }
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


// Ref
    public function command_ref() {
        coreLib\dump($this);
    }


// Section
    protected $_sectionCounter = 0;

    public function command_section($isStar) {
        $section = new flex\latex\map\Section($this->parser->token->getLocation());
        $section->setLevel(1);

        if(!$isStar) {
            $section->setNumber(++$this->_sectionCounter);
        }

        $this->_subsectionCounter = 0;

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($section, true);
        $this->parser->extractValue('}');

        return $section;
    }


// Subsection
    protected $_subsectionCounter = 0;

    public function command_subsection($isStar) {
        $section = new flex\latex\map\Section($this->parser->token->getLocation());
        $section->setLevel(2);

        if(!$isStar) {
            $section->setNumber(++$this->_subsectionCounter);
        }

        $this->_subsubsectionCounter = 0;

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($section, true);
        $this->parser->extractValue('}');

        return $section;
    }


// Subsubsection
    protected $_subsubsectionCounter = 0;

    public function command_subsubsection($isStar) {
        $section = new flex\latex\map\Section($this->parser->token->getLocation());
        $section->setLevel(3);

        if(!$isStar) {
            $section->setNumber(++$this->_subsubsectionCounter);
        }

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($section, true);
        $this->parser->extractValue('}');

        return $section;
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
        $options = array();

        if($this->parser->extractIfValue('[')) {
            while(true) {
                $word = $this->parser->extractMatch('word');
                $option = $word->value;
                $value = true;

                if($this->parser->extractIfValue('=')) {
                    $value = $this->parser->extractMatch('word')->value;
                } 

                $options[$option] = $value;

                if($this->parser->extractIfValue(']')) {
                    break;
                } else {
                    $this->parser->extractValue(',');
                }
            }
        }

        // Names
        $this->parser->extractValue('{');

        while(true) {
            $word = $this->parser->extractMatch('word');
            $this->parser->document->addPackage($word->value, $options);

            if($this->parser->extractIfValue('}')) {
                break;
            } else {
                $this->parser->extractValue(',');
            }
        }
    }
}