<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex;

use DecodeLabs\Dictum;

use df\flex;

abstract class TextCase implements ICase
{
    public static function normalizeCaseFlag($case): int
    {
        if (is_string($case)) {
            switch (strtolower(Dictum::id($case))) {
                case 'words':
                case 'upperwords':
                    $case = flex\ICase::UPPER_WORDS;
                    break;

                case 'upperfirst':
                    $case = flex\ICase::UPPER_FIRST;
                    break;

                case 'upper':
                    $case = flex\ICase::UPPER;
                    break;

                case 'lower':
                    $case = flex\ICase::LOWER;
                    break;

                case 'lowerfirst':
                    $case = flex\ICase::LOWER_FIRST;
                    break;

                default:
                    $case = flex\ICase::NONE;
                    break;
            }
        }

        switch ($case) {
            case flex\ICase::UPPER_WORDS:
            case flex\ICase::UPPER_FIRST:
            case flex\ICase::UPPER:
            case flex\ICase::LOWER:
            case flex\ICase::LOWER_FIRST:
                break;

            default:
                $case = flex\ICase::NONE;
                break;
        }

        return $case;
    }

    public static function apply($string, $case, $encoding = flex\IEncoding::UTF_8): ?string
    {
        if (null === ($text = Dictum::text($string, $encoding))) {
            return null;
        }

        $case = self::normalizeCaseFlag($case);

        switch ($case) {
            case flex\ICase::UPPER_WORDS:
                return (string)$text->toTitleCase();

            case flex\ICase::UPPER_FIRST:
                return (string)$text->firstToUpperCase();

            case flex\ICase::UPPER:
                return (string)$text->toUpperCase();

            case flex\ICase::LOWER:
                return (string)$text->toLowerCase();

            case flex\ICase::LOWER_FIRST:
                return (string)$text->firstToLowerCase();
        }

        return (string)$text;
    }
}
