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
    
class Hyperref extends Base {

    protected static $_commands = [
        'href', 'hyperref', 'url'
    ];


    public function command_href() {
        if($this->parser->getLastToken()->isWhitespaceSingleNewLine()) {
            $this->parser->writeToTextNode(' ');
        }

        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('href');
        $block->isInline(true);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true, false);
        $this->parser->extractValue('}');

        $url = $block->shift();
        $block->setAttribute('href', $url->getText());

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true);
        $this->parser->extractValue('}');

        return $block;
    }

    public function command_url() {
        if($this->parser->getLastToken()->isWhitespaceSingleNewLine()) {
            $this->parser->writeToTextNode(' ');
        }

        $block = new flex\latex\map\Block($this->parser->token);
        $block->setType('href');
        $block->isInline(true);

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($block, true);
        $this->parser->extractValue('}');

        $url = $block->shift();
        $block->setAttribute('href', $url->getText());
        $block->push($url);

        return $block;
    }
}