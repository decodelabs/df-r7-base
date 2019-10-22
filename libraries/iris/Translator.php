<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch;

abstract class Translator implements ITranslator
{
    public $unit;

    public function __construct($unit)
    {
        if (is_string($unit)) {
            if (is_file($unit)) {
                $unit = new iris\source\File($unit);
            } else {
                $unit = new iris\source\Memory($unit);
            }
        }

        if ($unit instanceof iris\ISource) {
            $unit = $this::createLexer($unit);
        }

        if ($unit instanceof iris\Lexer) {
            $unit = $this::createParser($unit);
        }

        if ($unit instanceof iris\IParser) {
            if (!$unit->hasRun()) {
                $unit->parse();
            }

            $unit = $unit->getUnit();
        }

        if (!$unit instanceof iris\map\IUnit) {
            throw new InvalidArgumentException(
                'Invalid unit passed to translator'
            );
        }

        $this->unit = $unit;
    }

    public static function createLexer(iris\ISource $source)
    {
        Glitch::incomplete($source);
    }

    public static function createParser(iris\Lexer $lexer)
    {
        Glitch::incomplete($lexer);
    }
}
