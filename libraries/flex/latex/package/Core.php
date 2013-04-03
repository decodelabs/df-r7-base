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
    
class Core extends Base {

    protected static $_commands = [
        '@' => 'symAt',
        '\\' => 'symBackslash',
        '\\*' => 'symBackslashStar',
        ',' => 'symComma',
        ';' => 'symSemiColon',
        ':' => 'symColon',
        '!' => 'symBang',
        '-' => 'symMinus',
        '=' => 'symEqual',
        '>' => 'symGt',
        '<' => 'symLt',
        '+' => 'symPlus',
        '\'' => 'symSQuote',
        '`' => 'symTick',
        '|' => 'symPipe',
        '(' => 'symOParen',
        ')' => 'symCParen',
        '[' => 'symOSBracket',
        ']' => 'symCSBracket',

        'addcontentsline', 'addtocontents', 'addtocounter', 'address', 'addtolength', 'addvspace', 'alph'
        'appendix', 'arabic', 'author', 'backslash', 'baselineskip', 'baselinestretch', 'bf', 'bibitem',
        'bigskipamount', 'bigskip', 'boldmath', 'cal', 'caption', 'cdots', 'centering', 'chapter', 'circle',
        'cite', 'cleardoublepage', 'clearpage', 'closing', 'copyright', 'dashbox', 'date',
        'ddots', 'documentclass', 'dotfill', 'em', 'emph', 'ensuremath', 'fbox', 'flushbottom', 'fnsymbol',
        'footnote', 'footnotemark', 'footnotesize', 'footnotetext', 'frac', 'frame', 'framebox', 'frenchspacing',
        'hfill', 'hrulefill', 'hspace', 'huge', 'Huge', 'hyphenation', 'include', 'includeonly', 'indent', 
        'input', 'it', 'item', 'kill', 'label', 'large', 'Large', 'LARGE', 'LaTeX', 'LaTeXe', 'ldots', 'left',
        'lefteqn', 'line', 'linebreak', 'linethickness', 'linewidth', 'listoffigures', 'listoftables', 
        'location', 'makebox', 'maketitle', 'markboth', 'markright', 'mathcal', 'mathop', 'mbox', 'medskip',
        'multicolumn', 'multiput', 'newcommand', 'newcounter', 'newenvironment', 'newfont', 'newlength',
        'newline', 'newpage', 'newsavebox', 'newtheorem', 'nocite', 'noindent', 'nolinebreak', 
        'nonfrenchspacing', 'normalsize', 'nopagebreak', 'not', 'onecolumn', 'opening', 'oval', 'overbrace',
        'overline', 'pagebreak', 'pagenumbering', 'pageref', 'pagestyle', 'par', 'paragraph', 'parbox',
        'parindent', 'parskip', 'part', 'protect', 'providecommand', 'put', 'raggedbottom', 'raggedleft',
        'raggedright', 'raisebox', 'ref', 'renewcommand', 'right', 'rm', 'roman', 'rule', 'savebox', 'sbox',
        'sc', 'scriptsize', 'section', 'setcounter', 'setlength', 'settowidth', 'sf', 'shortstack', 
        'signature', 'sl', 'slash', 'small', 'smallskip', 'sout', 'space', 'sqrt', 'stackrel', 'stepcounter',
        'subparagraph', 'subsection', 'subsubsection', 'tableofcontents', 'telephone', 'TeX', 'textbf',
        'textit', 'textmd', 'textnormal', 'textrm', 'textsc', 'textsf', 'textsl', 'texttt', 'textup', 
        'textwidth', 'textheight', 'thanks', 'thispagestyle', 'tiny', 'title', 'today', 'tt', 'twocolumn',
        'typeout', 'typein', 'uline', 'underbrace', 'underline', 'unitlength', 'usebox', 'usecounter', 
        'uwave', 'value', 'vbox', 'vcenter', 'vdots', 'vector', 'verb', 'vfill', 'vline', 'vphantom', 'vspace'
    ];
}