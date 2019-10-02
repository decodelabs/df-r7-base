<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Symbol implements iris\IScanner, Inspectable
{
    protected $_symbols = [];

    public function __construct($symbols=[])
    {
        $this->setSymbols($symbols);
    }

    public function getName(): string
    {
        return 'Symbol';
    }

    public function getWeight()
    {
        return 1000;
    }

    public function setSymbols(array $symbols)
    {
        return $this->clearSymbols()
            ->addSymbols($symbols);
    }

    public function addSymbols(array $symbols, $type=null)
    {
        foreach ($symbols as $key => $value) {
            if (is_array($value)) {
                if (is_string($key)) {
                    $keyType = $key;
                } else {
                    $keyType = $type;
                }

                $this->addSymbols($value, $keyType);
            } elseif (is_string($key)) {
                $this->addSymbol($key, $value);
            } else {
                $this->addSymbol($value, $type);
            }
        }

        return $this;
    }

    public function addSymbol($symbol, $type=null)
    {
        $symbol = trim($symbol);

        if ($type === null) {
            $type = 'default';
        }

        $this->_symbols[$symbol] = $type;
        return $this;
    }

    public function hasSymbol($symbol)
    {
        return isset($this->_symbols[$symbol]);
    }

    public function removeSymbol($symbol)
    {
        unset($this->_symbols[$symbol]);
        return $this;
    }

    public function clearSymbols()
    {
        $this->_symbols = [];
        return $this;
    }


    public function initialize(iris\ILexer $lexer)
    {
        if (empty($this->_symbols)) {
            throw new iris\LogicException(
                'Symbol processor does not have any symbols to match'
            );
        }

        uksort($this->_symbols, function ($a, $b) {
            return mb_strlen($a) < mb_strlen($b);
        });
    }


    public function check(iris\ILexer $lexer)
    {
        return true;
    }

    public function run(iris\ILexer $lexer)
    {
        $symbols = [];

        foreach ($this->_symbols as $symbol => $type) {
            if (mb_substr($symbol, 0, 1) != $lexer->char) {
                continue;
            }

            $length = mb_strlen($symbol);

            if ($lexer->peek(0, $length) == $symbol) {
                $lexer->extract($length);
                return $lexer->newToken('symbol', $symbol);
            }
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*symbols' => $inspector(count($this->_symbols))
            ]);
    }
}
