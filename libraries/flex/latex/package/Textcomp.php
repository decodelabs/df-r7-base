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
    
class Textcomp extends Base implements flex\latex\IActivePackage {

    public static function getCommandList() {
        return array_merge(
            self::$_special,
            array_keys(self::$_characterMap),
            array_keys(self::$_text)
        );
    }

    public function parseCommand($command) {
        if(isset(self::$_characterMap[$command])) {
            return $this->extractCharacterSymbol($command);
        }

        if(isset(self::$_text[$command])) {
            return self::$_text[$command];
        }

        if(in_array($command, self::$_special)) {
            return $command;
        }

        return $command;
    }

    public function extractCharacterSymbol($symbol) {
        $this->parser->extractValue('{');
        $letter = $utf8 = $this->parser->extractWord()->value;

        if($symbol == 't') {
            $letter = $utf8 = $letter.$this->parser->extractWord()->value;
        }

        if($symbol == 'H') {
            $symbol = '"';
        }

        if(isset(self::$_characterMap[$symbol][$letter])) {
            $utf8 = self::$_characterMap[$symbol][$letter];
        }

        $this->parser->writeToTextNode($utf8);
        $this->parser->extractValue('}');
    }

    protected static $_special = ['$', '%', '_', '{', '}', '&', '#'];

    protected static $_text = [
        'aa' => 'å',
        'AA' => 'Å',
        'ae' => 'æ',
        'AE' => 'Æ',
        'copyright' => '©',
        'dag' => '†',
        'ddag' => '‡',
        'dh' => 'ð',
        'DH' => 'Ð',
        'dj' => 'đ',
        'DJ' => 'Đ',
        'dots' => '…',
        'l' => 'ł',
        'L' => 'Ł',
        'ng' => 'ŋ',
        'NG' => 'Ŋ',
        'o' => 'ø',
        'O' => 'Ø',
        'oe' => 'œ',
        'OE' => 'Œ',
        'P' => '¶',
        'pounds' => '£',
        'S' => '§',
        'ss' => 'ß',
        'SS' => 'SS',
        'th' => 'þ',
        'TH' => 'Þ',

        'guilsinglleft' => '‹',       
        'guilsinglright' => '›',          
        'guillemotleft' => '«',        
        'guillemotright' => '»',          
        'quotedblbase' => '„',        
        'quotesinglbase' => '‚',
        'textquotedbl' => '"',

        'textasciiacute' => '´',          
        'textasciibreve' => '˘',          
        'textasciicaron' => 'ˇ',          
        'textasciidieresis' => '¨',        
        'textasciigrave' => '`',
        'textasciimacron' => '¯',
        'textacutedbl' => '̋ ',        
        'textgravedbl' => '̏ ',


        'textasciicircum' => '^',
        'textasciitilde' => '~',
        'textasteriskcentered' => '∗',
        'textbackslash' => '\\',
        'textbar' => '|',
        'textbardbl' => '‖',
        'textbigcircle' => '◯',
        'textbraceleft' => '{',
        'textbraceright' => '}',
        'textbrokenbar' => '¦',       
        'textbullet' => '•',
        'textcircledP' => '℗',          
        'textcopyright' => '©',
        'textdagger' => '†',
        'textdaggerdbl' => '‡',
        'textdblhyphen' => '=',
        'textdblhyphenchar' => '=',
        'textdiscount' => '⁒',
        'textdollar' => '$',
        'textellipsis' => '…',
        'textemdash' => '—',
        'textendash' => '–',
        'textestimated' => '℮',
        'textexclamdown' => '¡',
        'textgreater' => '>',
        'textinterrobang' => '‽',
        'textless' => '<',
        'textmusicalnote' => '♪',
        'textnumero' => '№',
        'textopenbullet' => '○',
        'textordfeminine' => 'ª',
        'textordmasculine' => 'º',
        'textparagraph' => '¶',
        'textperthousand' => '‰',
        'textpertenthousand' => '‱',
        'textperiodcentered' => '·',
        'textpilcrow' => '¶',
        'textquestiondown' => '¿',
        'textquotedblleft' => '“',
        'textquotedblright' => '”',
        'textquoteleft' => '‘',
        'textquoteright' => '’',
        'textquotesingle' => '\'',
        'textrecipe' => '℞',
        'textreferencemark' => '※',
        'textregistered' => '®',
        'textsection' => '§',
        'textservicemark' => '℠',       
        'textsterling' => '£',
        'texttildelow' => '˷',
        'texttrademark' => '™',
        'textunderscore' => '_',
        'textvisiblespace' => '␣'
    ];

    protected static $_characterMap = [
        '`' => [   // \`{o}   ò   grave accent
            'A' => 'À',
            'a' => 'à',
            'E' => 'È',
            'e' => 'è',
            'I' => 'Ì',
            'i' => 'ì',
            'O' => 'Ò',
            'o' => 'ò',
            'U' => 'Ù',
            'u' => 'ù'
        ], 
        '\'' => [   // \'{o}   ó   acute accent
            'A' => 'Á',
            'a' => 'á',
            'E' => 'É',
            'e' => 'é',
            'I' => 'Í',
            'i' => 'í',
            'O' => 'Ó',
            'o' => 'ó',
            'U' => 'Ú',
            'u' => 'ú',
            'Y' => 'Ý',
            'y' => 'ý'
        ], 
        '^' => [   // \^{o}   ô   circumflex
            'A' => 'Â',
            'a' => 'â',
            'E' => 'Ê',
            'e' => 'ê',
            'I' => 'Î',
            'i' => 'î',
            'O' => 'Ô',
            'o' => 'ô',
            'U' => 'Û',
            'u' => 'û'
        ], 
        '"' => [   // \"{o}   ö   umlaut, trema or dieresis
            'A' => 'Ä',
            'a' => 'ä',
            'E' => 'Ë',
            'e' => 'ë',
            'I' => 'Ï',
            'i' => 'ï',
            'O' => 'Ö',
            'o' => 'ö',
            'U' => 'Ü',
            'u' => 'ü',
            'y' => 'ÿ'
        ], 
        'H' => [], // \H{o}   ő   long Hungarian umlaut (double acute)
        '~' => [   // \~{o}   õ   tilde
            'A' => 'Ã',
            'a' => 'ã',
            'N' => 'Ñ',
            'n' => 'ñ',
            'O' => 'Õ',
            'o' => 'õ'
        ], 
        'c' => [   // \c{c}   ç   cedilla
            'C' => 'Ç',
            'c' => 'ç',
            'G' => 'Ģ',
            'K' => 'Ķ',
            'k' => 'ķ',
            'L' => 'Ļ',
            'l' => 'ļ',
            'N' => 'Ņ',
            'n' => 'ņ',
            'R' => 'Ŗ',
            'r' => 'ŗ',
            'S' => 'Ş',

        ], 
        'k' => [   // \k{a}   ą   ogonek
            'A' => 'Ą',
            'a' => 'ą',
            'E' => 'Ę',
            'e' => 'ę',
            'I' => 'Į',
            'i' => 'į',
            'U' => 'Ų',
            'u' => 'ų'
        ], 
        '=' => [   // \={o}   ō   macron accent (a bar over the letter)
            'A' => 'Ā',
            'a' => 'ā',
            'E' => 'Ē',
            'e' => 'ē',
            'I' => 'Ī',
            'i' => 'ī',
            'O' => 'Ō',
            'o' => 'ō',
            'U' => 'Ū',
            'u' => 'ū'
        ], 
        'b' => [], // \b{o}   o   bar under the letter
        '.' => [   // \.{o}   ȯ   dot over the letter
            'C' => 'Ċ',
            'c' => 'ċ',
            'E' => 'Ė',
            'e' => 'ė',
            'G' => 'Ġ',
            'g' => 'ġ',
            'Z' => 'Ż',
            'z' => 'ż'
        ], 
        'd' => [], // \d{u}   ụ  dot under the letter
        'r' => [   // \r{a}   å   ring over the letter (for å there is also the special command \aa)
            'A' => 'Å',
            'a' => 'å',
            'U' => 'Ů',
            'u' => 'ů'
        ], 
        'u' => [   // \u{o}   ŏ   breve over the letter
            'A' => 'Ă',
            'a' => 'ă',
            'E' => 'Ĕ',
            'e' => 'ĕ',
            'G' => 'Ğ',
            'g' => 'ğ',
            'I' => 'Ĭ',
            'i' => 'ĭ',
            'O' => 'Ŏ',
            'o' => 'ŏ',
            'U' => 'Ŭ',
            'u' => 'ŭ'
        ], 
        'v' => [   // \v{s}   š   caron/hacek ("v") over the letter
            'C' => 'Č',
            'c' => 'č',
            'E' => 'Ě',
            'e' => 'ě',
            'N' => 'Ň',
            'n' => 'ň',
            'R' => 'Ř',
            'r' => 'ř',
            'T' => 'Ť',
            'Z' => 'Ž',
            'z' => 'ž'
        ], 
        't' => [   // \t{oo}  o͡o "tie" (inverted u) over the two letters
            'oo' => 'o͡o'
        ]
    ];
}