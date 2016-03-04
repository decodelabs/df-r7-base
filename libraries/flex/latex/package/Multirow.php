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

class Multirow extends Base {

    const COMMANDS = [
        'multirow'
    ];


    public function command_multirow() {
        if(!$this->parser->container instanceof flex\latex\IGenericBlock
        || $this->parser->container->getType() != 'cell') {
            throw new iris\UnexpectedTokenException(
                'Not in a cell', $this->parser->token
            );
        }


        $this->parser->extractValue('{');
        $rows = $this->parser->extractWord();
        $this->parser->container->setAttribute('rowspan', (int)$rows->value);
        $this->parser->extractValue('}');

        $this->parser->extractValue('{');
        $this->parser->parseStandardContent($this->parser->container, true, false);
        $this->parser->extractValue('}');

        if($this->parser->token->value == '{') {
            $width = $this->parser->container->reduceContents(); // Don't care right now!

            $this->parser->extractValue('{');
            $this->parser->parseStandardContent($this->parser->container, true, false);
            $this->parser->extractValue('}');
        }

        return $this->parser->container;
    }
}