<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

class Inspector implements IInspector {

    protected $_rules = [];

    protected $_command;
    protected $_valueArguments = [];
    protected $_optionArguments = [];

    public function __construct($rules, $command=null) {
        if(is_string($rules)) {
            $this->_parseCompact($rules);
        } else if(is_array($rules)) {
            $this->_parse($rules);
        } else {
            throw core\Error::EArgument('Invalid rules');
        }

        if($command !== null) {
            $this->inspect($command);
        }
    }

    protected function _parseCompact(string $string): void {
        $length = strlen($string);
        $lastRule = null;

        for($i = 0; $i < $length; $i++) {
            $char = $string{$i};

            if(!preg_match('/^[a-zA-Z\:]$/', $char)) {
                throw core\Error::EArgument(
                    'Invalid option character: '.$char
                );
            }

            if($char == ':') {
                $lastRule->requiresValue(true);
            } else {
                $lastRule = new Rule($char);
                $this->_exportRule($lastRule);
            }
        }
    }

    protected function _parse(array $options): void {
        foreach($options as $key => $description) {
            if(!preg_match('/^([a-zA-Z|]+)(([\-=])(s|i|w))?$/', $key, $matches)) {
                throw core\Error::EArgument(
                    'Invalid rule definition: '.$key
                );
            }

            $rule = new Rule($matches[1]);

            if(isset($matches[3])) {
                if($matches[3] == '=') {
                    $rule->requiresValue(true);
                } else if($matches[3] == '-') {
                    $rule->canHaveValue(true);
                } else {
                    $rule->canHaveValue(false);
                }

                $rule->setValueType($matches[4]);
            }

            $this->_exportRule($rule);
        }
    }


    public function inspect($command) {
        $this->reset();
        $this->_command = Command::factory($command);
        $arguments = $this->_command->getArguments();
        $nextIsOptionValue = false;
        $lastArgument = null;
        $lastRule = null;

        while($argument = array_shift($arguments)) {
            if($nextIsOptionValue) {
                if($argument->isOption()) {
                    throw core\Error::EArgument(
                        'Expecting option value for '.$lastArgument->getOption().' flag, not '.$argument
                    );
                }

                $lastArgument->setValue($argument->getValue());
                $this->_testValue($lastRule, $lastArgument);
                $nextIsOptionValue = false;
                continue;
            }

            if(!$argument->isOption()) {
                $this->_valueArguments[] = $argument;
                continue;
            }

            $optionStrings = $argument->getOptions();
            $value = $argument->getValue();

            while($optionString = array_shift($optionStrings)) {
                if(!isset($this->_rules[$optionString])) {
                    continue;
                }

                $lastArgument = (new Argument(null))
                    ->setOption($optionString)
                    ->setValue($value);

                $lastRule = $this->_rules[$optionString];
                $this->_storeOptionArgument($lastRule, $lastArgument);

                if($lastRule->requiresValue()) {
                    if(!$lastArgument->hasValue()) {
                        if(null !== ($default = $lastRule->getDefaultValue())) {
                            $lastArgument->setValue($default);
                        } else {
                            if(!empty($optionStrings)) {
                                throw core\Error::EArgument(
                                    'Option '.$optionString.' requires a value so cannot be clustered'
                                );
                            }

                            $nextIsOptionValue = true;
                            continue 2;
                        }
                    } else {
                        $this->_testValue($lastRule, $lastArgument);
                    }
                } else if(!$lastRule->canHaveValue()) {
                    $lastArgument->setValue(null);
                }
            }
        }

        foreach($this->_rules as $rule) {
            if($rule->isRequired() && isset($this->_optionArguments[$rule->getName()])) {
                throw core\Error::EArgument(
                    'Option '.$rule->getName().' is required'
                );
            }
        }

        return $this;
    }

    protected function _exportRule(IRule $rule): void {
        foreach($rule->getFlags() as $flag) {
            $this->_rules[$flag] = $rule;
        }
    }

    protected function _storeOptionArgument(IRule $rule, IArgument $argument): void {
        foreach($rule->getNames() as $name) {
            $this->_optionArguments[$name] = $argument;
        }
    }

    protected function _testValue(IRule $rule, IArgument $argument): void {
        $type = $rule->getValueType();
        $value = $argument->getValue();

        switch($type->getValue()) {
            case ValueType::INTEGER:
                if(!is_numeric($value)) {
                    throw core\Error::Evalue(
                        'Expected integer value for '.$rule->getName().' rule'
                    );
                }

                $argument->setValue((int)$value);
                break;

            case ValueType::STRING:
                break;

            case ValueType::WORD:
                if(!preg_match('/^[a-zA-Z]+$/', $value)) {
                    throw core\Error::Evalue(
                        'Expected word value for '.$rule->getName().' rule'
                    );
                }
                break;
        }
    }

    public function reset() {
        $this->_command = null;
        $this->_valueArguments = [];
        $this->_optionArguments = [];
        return $this;
    }

    public function getCommand(): ?ICommand {
        return $this->_command;
    }

    public function getValueArguments(): array {
        return $this->_valueArguments;
    }

    public function getOptionArguments(): array {
        return $this->_optionArguments;
    }


    public function __get($member) {
        if(isset($this->_optionArguments[$member])) {
            return $this->_optionArguments[$member];
        }
    }

    public function offsetSet($key, $value) {
        if(!$value instanceof IArgument) {
            $optionString = $key;

            if(substr($optionString, 0, 1) != '-') {
                if(strlen($optionString) == 1) {
                    $optionString = '-'.$optionString;
                } else {
                    $optionString = '--'.$optionString;
                }
            }

            $value = (new Argument(null))
                ->setOption($optionString)
                ->setValue($value);
        }

        $this->_optionArguments[$key] = $value;
        return $this;
    }

    public function offsetGet($key) {
        if(!isset($this->_optionArguments[$key])) {
            return false;
        }

        $argument = $this->_optionArguments[$key];

        if($argument->hasValue()) {
            return $argument->getValue();
        } else {
            return true;
        }
    }

    public function offsetExists($key) {
        return isset($this->_optionArguments[$key]);
    }

    public function offsetUnset($key) {
        unset($this->_optionArguments[$key]);
    }
}
