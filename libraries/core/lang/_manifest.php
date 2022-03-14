<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\lang;

use df;
use df\core;

use DecodeLabs\Typify;

interface ICallback
{
    public const DIRECT = 1;
    public const REFLECTION = 2;

    public static function getCallableId(callable $callable);

    public function setExtraArgs(array $args);
    public function getExtraArgs();

    public function invoke(...$args);

    public function getParameters();
}

function Callback($callback, ...$args)
{
    return Callback::call($callback, ...$args);
}

interface IEnumFactory
{
    public function factory($value);
}

interface IEnum extends core\IStringProvider, core\IStringValueProvider
{
    //public static function factory($value);
    public static function normalize($value);

    public function getIndex();
    public function getOption();
    public function getLabel();

    public function is($value);
}

interface IStaticEnum extends IEnum
{
    public static function getOptions();
    public static function isOption($option);
    public static function getLabels();

    public static function getLt($option): array;
    public static function getLte($option): array;
    public static function getGt($option): array;
    public static function getGte($option): array;

    public static function label($option);
}

interface IInstanceEnum extends IEnum
{
    public function getOptions();
    public function isOption($option);
    public function getLabels();

    public function getLt($option): array;
    public function getLte($option): array;
    public function getGt($option): array;
    public function getGte($option): array;

    public function label($option);
}




interface IStruct
{
    public function import(array $data);
}


interface ITypeRef
{
    public function newInstance(...$args);
    public function checkType($extends);
    public function getClass();
    public function getClassPath();
}


// Chaining
interface IChainable
{
    public function chain($callback, ...$args);
    public function chainIf($test, $trueCallback, $falseCallback=null);
    public function chainEach(array $list, $callback, ...$args);
}

trait TChainable
{
    public function chain($callback, ...$args)
    {
        Callback::call($callback, $this, ...$args);
        return $this;
    }

    public function chainIf($test, $trueCallback, $falseCallback=null)
    {
        if ($test) {
            Callback::call($trueCallback, $this);
        } elseif ($falseCallback) {
            Callback::call($falseCallback, $this);
        }

        return $this;
    }

    public function chainEach(array $list, $callback, ...$args)
    {
        if (!$callback = Callback::factory($callback)) {
            return $this;
        }

        foreach ($list as $key => $value) {
            $callback->invoke($this, $value, $key, ...$args);
        }

        return $this;
    }
}



// Future
interface IFuture extends core\IValueContainer
{
    public function __invoke();
}



// Accept type
interface IAcceptTypeProcessor
{
    public function setAcceptTypes(...$types);
    public function addAcceptTypes(...$types);
    public function getAcceptTypes();
    public function isTypeAccepted(...$types);
}

trait TAcceptTypeProcessor
{
    protected $_acceptTypes = [];

    public function setAcceptTypes(...$types)
    {
        $this->_acceptTypes = [];
        return $this->addAcceptTypes(...$types);
    }

    public function addAcceptTypes(...$types)
    {
        foreach ($types as $type) {
            $type = trim(strtolower($type));

            if (!strlen($type)) {
                continue;
            }

            if ($type[0] == '.') {
                $type = Typify::detect($type);
            }

            if (false === strpos($type, '/')) {
                $type .= '/*';
            }

            if (!in_array($type, $this->_acceptTypes)) {
                $this->_acceptTypes[] = $type;
            }
        }

        return $this;
    }

    public function getAcceptTypes()
    {
        return $this->_acceptTypes;
    }

    public function isTypeAccepted(...$types)
    {
        if (empty($this->_acceptTypes)) {
            return true;
        }

        foreach ($types as $type) {
            if (!strlen($type)) {
                continue;
            }

            if ($type[0] == '.') {
                $type = Typify::detect($type);
            }

            @list($category, $name) = explode('/', $type, 2);

            foreach ($this->_acceptTypes as $accept) {
                if ($accept == '*') {
                    return true;
                }

                @list($acceptCategory, $acceptName) = explode('/', $accept, 2);

                if ($acceptCategory == '*') {
                    return true;
                }

                if ($acceptCategory != $category) {
                    continue;
                }

                if ($acceptName == '*') {
                    return true;
                }

                if ($acceptName != $name) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }
}
