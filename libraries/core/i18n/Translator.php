<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\i18n;

use DecodeLabs\Exceptional;

class Translator implements ITranslator
{
    protected $_domainId;

    public static function factory($domainId, $locale = null)
    {
        return new self($domainId);
    }

    protected function __construct($domainId)
    {
        $this->_domainId = $domainId;
    }

    public function getDomainId()
    {
        return $this->_domainId;
    }

    public function _($phrase = '', $b = null, $c = null): string
    {
        return $this->translate(func_get_args());
    }

    public function translate(array $args): string
    {
        $phrase = array_shift($args);
        $plural = false;

        if (is_array($phrase)) {
            $plural = array_pop($args);

            if (!is_numeric($plural)) {
                throw Exceptional::InvalidArgument(
                    'The last parameter to a plural translation must be the number of items'
                );
            }
        }

        if (!empty($args)) {
            $replacements = array_shift($args);
        } else {
            $replacements = [];
        }

        $output = $this->_fetch($phrase, $plural !== false);

        if ($plural !== false) {
            if (!isset($replacements['%plural%'])) {
                $replacements['%plural%'] = $plural;
            }

            $output = $this->_formatPluralPhrase($output, $plural);
        }


        if (!empty($replacements)) {
            foreach ($replacements as $key => $value) {
                $replacements[$key] = (string)$value;
            }

            $output = strtr($output, $replacements);
        }

        return $output;
    }

    protected function _fetch($phrase, $isPlural)
    {
        if ($isPlural) {
            $matchPhrase = [];

            foreach ($phrase as $key => $value) {
                $matchPhrase[] = '[' . $key . ']' . $value;
            }

            $matchPhrase = implode('|', $matchPhrase);
        } else {
            $matchPhrase = (string)$phrase;
        }

        // TODO: Lookup phrase

        if ($isPlural) {
            return $phrase;
        } else {
            return (string)$phrase;
        }
    }

    protected function _formatPluralPhrase(array $options, $plural)
    {
        foreach ($options as $key => $phrase) {
            if (is_numeric($key)) {
                if ($key == $plural) {
                    return $phrase;
                } else {
                    continue;
                }
            }

            if ($key == '*') {
                return $phrase;
            }

            $parts = explode('||', $key);

            foreach ($parts as $part) {
                $clauses = explode('&&', str_replace(' ', '', $part));

                foreach ($clauses as $clause) {
                    if (!preg_match('/n(([\/\*\+\-\%])([-0-9])+)?([<>=]+)([-0-9]+)/', $clause, $matches)) {
                        throw Exceptional::InvalidArgument(
                            $clause . ' is not a valid plural clause'
                        );
                    }

                    $test = $plural;

                    if (isset($matches[1]) && strlen((string)$matches[1])) {
                        switch ($matches[2]) {
                            case '/':
                                $test /= $matches[3];
                                break;

                            case '*':
                                $test *= $matches[3];
                                break;

                            case '+':
                                $test += $matches[3];
                                break;

                            case '-':
                                $test -= $matches[3];
                                break;

                            case '%':
                                $test %= $matches[3];
                                break;
                        }
                    }

                    switch ($matches[4]) {
                        case '>=':
                            if (!($test >= $matches[5])) {
                                continue 3;
                            }
                            break;

                        case '<=':
                            if (!($test <= $matches[5])) {
                                continue 3;
                            }
                            break;

                        case '>':
                            if (!($test > $matches[5])) {
                                continue 3;
                            }
                            break;

                        case '<':
                            if (!($test < $matches[5])) {
                                continue 3;
                            }
                            break;

                        case '=':
                        case '==':
                            if (!($test == $matches[5])) {
                                continue 3;
                            }
                            break;

                        default:
                            continue 3;
                    }
                }

                return $phrase;
            }
        }

        return array_shift($options);
    }
}
