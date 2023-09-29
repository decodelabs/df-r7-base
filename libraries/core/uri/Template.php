<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\uri;

use DecodeLabs\Exceptional;

use df\core;

class Template implements ITemplate
{
    public const OPERATORS = [
        '' => ['',  ',', false],
        '+' => ['',  ',', false],
        '#' => ['#', ',', false],
        '.' => ['.', '.', false],
        '/' => ['/', '/', false],
        ';' => [';', ';', true],
        '?' => ['?', '&', true],
        '&' => ['&', '&', true]
    ];

    public const DELIMITERS = [
        ':', '/', '?', '#', '[', ']', '@', '!', '$',
        '&', '\'', '(', ')', '*', '+', ',', ';', '='
    ];

    public const ENCODED_DELIMITERS = [
        '%3A', '%2F', '%3F', '%23', '%5B', '%5D',
        '%40', '%21', '%24', '%26', '%27', '%28',
        '%29', '%2A', '%2B', '%2C', '%3B', '%3D'
    ];

    public const RFC_1738_TO_3986 = ['+' => '%20', '%7e' => '~'];

    protected $_template;
    protected $_values = [];

    public function __construct($template)
    {
        $this->_template = (string)$template;
    }

    public function expand(array $values)
    {
        if (false === strpos($this->_template, '{')) {
            return $this->_template;
        }

        $this->_values = $values;

        return preg_replace_callback(
            '/\{([^\}]+)\}/',
            [$this, '_handleMatch'],
            $this->_template
        );
    }

    protected function _handleMatch(array $matches)
    {
        $expression = $this->_parseExpression($matches[1]);
        list($prefix, $delimiter, $inQuery) = self::OPERATORS[$expression->operator];
        $output = [];

        foreach ($expression->specs as $spec) {
            if (!isset($this->_values[$spec->key])) {
                continue;
            }

            $value = $this->_values[$spec->key];
            $useQuery = $inQuery;
            $expanded = '';

            if (is_array($value)) {
                $isAssoc = core\collection\Util::isArrayAssoc($value);
                $values = [];

                foreach ($value as $key => $subValue) {
                    if ($isAssoc) {
                        $key = rawurlencode($key);
                        $isNested = is_array($subValue);
                    } else {
                        $isNested = false;
                    }

                    if (!$isNested) {
                        $subValue = rawurlencode($subValue);

                        if ($expression->operator == '+'
                        || $expression->operator == '#') {
                            $subValue = $this->_decodeReserved($subValue);
                        }
                    }

                    if ($spec->modifier == '*') {
                        if ($isAssoc) {
                            if ($isNested) {
                                $subValue = strtr(
                                    http_build_query([$key => $subValue]),
                                    self::RFC_1738_TO_3986
                                );
                            } else {
                                $subValue = $key . '=' . $subValue;
                            }
                        } elseif ($key > 0 && $useQuery) {
                            $subValue = $spec->key . '=' . $subValue;
                        }
                    } elseif ($isAssoc) {
                        $subValue = $key . ',' . $subValue;
                    }

                    $values[$key] = $subValue;
                }

                if (empty($value)) {
                    $useQuery = false;
                } elseif ($spec->modifier == '*') {
                    $expanded = implode($delimiter, $values);

                    if ($isAssoc) {
                        $useQuery = false;
                    }
                } else {
                    $expanded = implode(',', $values);
                }
            } else {
                if ($spec->modifier == ':') {
                    $value = substr($value, 0, $spec->position);
                }

                $expanded = rawurlencode($value);

                if ($expression->operator == '+'
                || $expression->operator == '#') {
                    $expanded = $this->_decodeReserved($expanded);
                }
            }

            if ($useQuery) {
                if (!$expanded && $delimiter != '&') {
                    $expanded = $spec->value;
                } else {
                    $expanded = $spec->value . '=' . $expanded;
                }
            }

            $output[] = $expanded;
        }

        $output = implode($delimiter, $output);

        if ($output && $prefix) {
            $output = $prefix . $output;
        }

        return $output;
    }

    protected function _parseExpression($expression)
    {
        if (!strlen((string)$expression)) {
            throw Exceptional::UnexpectedValue(
                'Empty template expression'
            );
        }

        $output = new Template_Expression();

        if (isset(self::OPERATORS[$expression[0]])) {
            $output->operator = $expression[0];
            $expression = substr($expression, 1);
        }

        foreach (explode(',', $expression) as $value) {
            $value = trim($value);
            $spec = new Template_ExpressionVarSpec();

            if (strpos($value, ':')) {
                $spec->modifier = ':';
                list($spec->key, $spec->position) = explode(':', $value, 2);
            } elseif (substr($value, -1) == '*') {
                $spec->modifier = '*';
                $spec->key = substr($value, 0, -1);
            } else {
                $spec->key = (string)$value;
            }

            $output->specs[] = $spec;
        }

        return $output;
    }

    protected function _decodeReserved($value)
    {
        return str_replace(self::ENCODED_DELIMITERS, self::DELIMITERS, $value);
    }
}

class Template_Expression
{
    public $operator = '';
    public $specs = [];
}

class Template_ExpressionVarSpec
{
    public $key;
    public $modifier;
    public $position;
}
