<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Argument implements IArgument, Inspectable
{
    use core\TStringProvider;

    protected $_option;
    protected $_value;

    public function __construct(?string $string)
    {
        if ($string !== null) {
            if (substr($string, 0, 1) != '-') {
                $this->_value = $string;
            } else {
                $parts = explode('=', $string, 2);
                $this->_option = array_shift($parts);
                $this->_value = array_shift($parts);
            }
        }
    }

    public function setOption(?string $option)
    {
        if (!strlen((string)$option)) {
            $option = null;
        }

        $this->_option = $option;
        return $this;
    }

    public function getOption(): ?string
    {
        return $this->_option;
    }

    public function getOptions(): array
    {
        $output = [];

        if ($this->isOptionCluster()) {
            for ($i = 1; $i < strlen($this->_option); $i++) {
                $output[] = '-'.$this->_option[$i];
            }
        } else {
            $output[] = $this->_option;
        }

        return $output;
    }

    public function isOption(): bool
    {
        return $this->_option !== null;
    }

    public function isLongOption(): bool
    {
        return substr($this->_option, 0, 2) == '--';
    }

    public function isShortOption(): bool
    {
        return substr($this->_option, 0, 1) == '-' && !$this->isLongOption();
    }

    public function isOptionCluster(): bool
    {
        return (bool)preg_match('/^-[a-zA-Z0-9]{2,}/', $this->_option);
    }

    public function getClusterOptions(): array
    {
        $output = [];

        if ($this->isOptionCluster()) {
            for ($i = 1; $i < strlen($this->_option); $i++) {
                $output[] = $this->_option[$i];
            }
        }

        return $output;
    }



    public function setValue(?string $value)
    {
        if (!strlen((string)$value)) {
            $value = null;
        }

        $this->_value = $value;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->_value;
    }

    public function hasValue(): bool
    {
        return $this->_value !== null;
    }

    public function toString(): string
    {
        $output = '';
        $hasValue = $this->hasValue();

        if ($this->_option !== null) {
            $output = $this->_option;

            if ($hasValue) {
                $output .= '=';
            }
        }

        if ($hasValue) {
            $output .= $this->_value;
        }

        return $output;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->toString());
    }
}
