<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\processor;

use df;
use df\core;
use df\iris;

class Type extends Base {

    const CONTEXT_CLASS = 'C';
    const CONTEXT_MIXIN = 'M';
    const CONTEXT_INTERFACE = 'I';
    const CONTEXT_ENUM = 'E';
    const CONTEXT_TRAIT = 'T';
    const CONTEXT_EXCEPTION = 'X';

    protected $_regex = null;
    protected $_allowContext = false;
    protected $_contextSeparator = '/';

    public function getName(): string {
        return 'Type';
    }

    public function setNameRegex($regex) {
        $this->_regex = $regex;
        return $this;
    }

    public function getNameRegex() {
        return $this->_regex;
    }

    public function shouldAllowContext(bool $flag=null) {
        if($flag !== null) {
            $this->_allowContext = $flag;
            return $this;
        }

        return $this->_allowContext;
    }

    public function setContextSeparator($symbol) {
        $this->_contextSeparator = $symbol;
        return $this;
    }

    public function getContextSeparator() {
        return $this->_contextSeparator;
    }

    public function initialize(iris\IParser $parser) {
        parent::initialize($parser);

        if(!$parser->getProcessor('UnitNamespace')) {
            throw new iris\LogicException(
                'Type processor is dependent on Namespace processor'
            );
        }
    }


    public function extractTypeName(iris\map\aspect\EntityNamespace $namespace=null, $defaultTypeContext=self::CONTEXT_CLASS) {
        $context = null;

        if($this->parser->peek(1)->matches('symbol', null, $this->_contextSeparator)) {
            if(!$this->_allowContext) {
                throw new iris\UnexpectedTokenException(
                    'Type context hints are not allowed',
                    $this->parser->peek(1)
                );
            }

            $context = $this->parser->extractWord();

            if(strlen($context->value) > 1) {
                throw new iris\UnexpectedTokenException(
                    'Invalid type context: '.$context->value,
                    $context
                );
            }

            $this->parser->extractMatch('symbol', null, $this->_contextSeparator);
            $context = $context->value;
        }

        $name = $this->parser->extractWord();

        if(!$this->testName($name->value)) {
            throw new iris\UnexpectedTokenException(
                'Invalid type name :'.$name->value,
                $name
            );
        }

        if(!$namespace) {
            if($this->parser->currentNamespace) {
                if($type = $this->parser->currentNamespace->getTypeShortcut($name->value, $context)) {
                    return $type->duplicate($name);
                }

                $namespace = $this->parser->currentNamespace->getNamespace()->duplicate($name);
            }

            if(!$namespace) {
                $namespace = $this->parser->unitNamespace->newNamespace();
            }
        }

        if($context === null) {
            $context = $defaultTypeContext;
        }

        return new iris\map\aspect\TypeReference($namespace, $name->value, $context);
    }

    public function testName($name) {
        if($this->_regex && !preg_match($this->_regex, $name)) {
            return false;
        }

        return true;
    }
}
