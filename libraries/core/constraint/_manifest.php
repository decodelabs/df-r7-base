<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\constraint;

## Required
interface IRequirable
{
    public function isRequired(bool $flag = null);
}

trait TRequirable
{
    protected $_isRequired = false;

    public function isRequired(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isRequired = $flag;
            return $this;
        }

        return $this->_isRequired;
    }
}



## Disabled
interface IDisableable
{
    public function isDisabled(bool $flag = null);
}

trait TDisableable
{
    protected $_isDisabled = false;

    public function isDisabled(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isDisabled = $flag;
            return $this;
        }

        return $this->_isDisabled;
    }
}


## Optional
interface IOptional
{
    public function isOptional(bool $flag = null);
}

trait TOptional
{
    protected $_isOptional = false;

    public function isOptional(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isOptional = $flag;
            return $this;
        }

        return $this->_isOptional;
    }
}



## Nullable
interface INullable
{
    public function isNullable(bool $flag = null);
}

trait TNullable
{
    protected $_isNullable = false;

    public function isNullable(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isNullable = $flag;
            return $this;
        }

        return $this->_isNullable;
    }
}



## Read only
interface IReadOnly
{
    public function isReadOnly(bool $flag = null);
}

trait TReadOnly
{
    protected $_isReadOnly = false;

    public function isReadOnly(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isReadOnly = $flag;
            return $this;
        }

        return $this->_isReadOnly;
    }
}
