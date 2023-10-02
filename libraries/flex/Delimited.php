<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Exceptional;

class Delimited implements IDelimited
{
    // Explode generator
    public static function splitLines($source, $trim = false)
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);

        if ($trim) {
            $source = trim((string)$source, "\n");
        }

        return self::split("\n", $source);
    }

    public static function split($delimiter, $source)
    {
        $length = strlen((string)$source);

        while ($length) {
            $pos = strpos($source, $delimiter);

            if ($pos === false) {
                yield $source;
                return;
            }

            yield substr($source, 0, $pos);
            $source = substr($source, $pos + 1);
            $length -= $pos + 1;
        }
    }


    // Parser
    public static function parse($input, $delimiter = ',', $quoteMap = '"\'', $terminator = null)
    {
        $output = [];

        foreach (self::iterate($input, $delimiter, $quoteMap, $terminator) as $row) {
            $output[] = $row;
        }

        return $output;
    }

    public static function iterate($input, $delimiter = ',', $quoteMap = '"\'', $terminator = null)
    {
        $input = trim((string)$input);

        if (!strlen($input)) {
            return;
        }

        if ($terminator !== null) {
            $row = [];
            $input .= $terminator;
        } else {
            $input .= $delimiter;
        }

        $length = strlen($input);
        $mode = 0;
        $cell = '';
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            switch ($mode) {
                // post delimiter or start
                case 0:
                    if (ctype_space($char)) {
                        break;
                    } elseif ($char == $delimiter) {
                        if ($terminator !== null) {
                            $row[] = $cell;
                        } else {
                            yield $cell;
                        }

                        $cell = '';
                    } elseif (strstr($quoteMap, $char)) {
                        $quote = $char;
                        $mode = 2;
                    } else {
                        $cell .= $char;
                        $mode = 1;
                    }

                    break;

                    // in cell
                case 1:
                    if ($terminator !== null && $char == $terminator) {
                        $row[] = $cell;
                        $cell = '';
                        yield $row;
                        $row = [];
                        $mode = 0;
                        break;
                    } elseif ($char == $delimiter) {
                        if ($terminator !== null) {
                            $row[] = $cell;
                        } else {
                            yield $cell;
                        }

                        $cell = '';
                    } else {
                        $cell .= $char;
                    }

                    break;

                    // in quote
                case 2:
                    if ($char == '\\') {
                        $mode = 3;
                    } elseif ($char == $quote) {
                        $mode = 4;
                    } else {
                        $cell .= $char;
                    }

                    break;

                    // escape in quote
                case 3:
                    $cell .= $char;
                    break;

                    // end of quote
                case 4:
                    $quote = null;

                    if (ctype_space($char) && $char != $terminator) {
                        break;
                    }

                    if ($terminator !== null && $char == $terminator) {
                        $row[] = $cell;
                        $cell = '';
                        yield $row;
                        $row = [];
                        $mode = 0;
                        break;
                    } elseif ($char == $delimiter) {
                        if ($terminator !== null) {
                            $row[] = $cell;
                        } else {
                            yield $cell;
                        }

                        $cell = '';
                        $mode = 0;
                        break;
                    }

                    throw Exceptional::UnexpectedValue(
                        'Unexpected character: ' . $char . ' at position ' . $i . ' in ' . $input
                    );
            }
        }
    }

    public static function implode(array $data, $delimiter = ',', $quote = '"', $terminator = null)
    {
        $output = [];

        if ($terminator !== null) {
            foreach ($data as $row) {
                foreach ($row as $key => $value) {
                    $row[$key] = $quote . str_replace($quote, '\\' . $quote, $value) . $quote;
                }

                $output[] = implode($delimiter, $row);
            }

            return implode($terminator, $output);
        } else {
            foreach ($data as $value) {
                $output[] = $quote . str_replace($quote, '\\' . $quote, $value) . $quote;
            }

            return implode($delimiter, $output);
        }
    }
}
