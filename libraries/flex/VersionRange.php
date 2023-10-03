<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Glitch\Dumpable;

use df\core;

class VersionRange implements core\IStringProvider, Dumpable
{
    use core\TStringProvider;

    protected $_groups = [];

    public static function factory($range)
    {
        if ($range instanceof VersionRange) {
            return $range;
        }

        return new self($range);
    }

    public function __construct(string $range = null)
    {
        $range = (string)preg_replace('/\s+/', ' ', (string)$range);
        $range = str_replace(['*', 'X'], 'x', $range);
        $parts = explode(' ', $range);
        $group = [];

        while (!empty($parts)) {
            $part = array_shift($parts);

            if (in_array($part, Version_Comparator::OPERATORS)) {
                $part .= array_shift($parts);
            }

            if ($part == '-') {
                if ($left = array_pop($group)) {
                    $left->operator = '>=';
                    $group[] = $left;
                }

                $part = '<=' . array_shift($parts);
            }

            if ($part == '||' || $part == 'or') {
                if (!empty($group)) {
                    $this->_groups[] = $group;
                    $group = [];
                }

                continue;
            } elseif ($part == '&&' || $part == 'and') {
                continue;
            }

            $this->_parseExpression($group, $part);
        }

        if (!empty($group)) {
            $this->_groups[] = $group;
        }
    }

    protected function _parseExpression(array &$group, $expression)
    {
        if (substr($expression, 0, 1) == '~') {
            $matches = Version::matchString(substr($expression, 1));

            if (!isset($matches[1])) {
                $group[] = new Version_Comparator('>=', '0');
                return;
            }

            $parts = explode('.', $matches[1]);

            if (!isset($parts[1])) {
                $group[] = new Version_Comparator('>=', $parts[0]);
                $group[] = new Version_Comparator('<', (int)$parts[0] + 1);
                return;
            } elseif (!isset($parts[2])) {
                $group[] = new Version_Comparator('>=', $parts[0] . '.' . $parts[1]);
                $group[] = new Version_Comparator('<', $parts[0] . '.' . ((int)$parts[1] + 1));
                return;
            } else {
                $group[] = new Version_Comparator('>=', $matches[0]);
                $group[] = new Version_Comparator('<', $parts[0] . '.' . ((int)$parts[1] + 1));
                return;
            }
        }

        if (substr($expression, 0, 1) == '^') {
            $matches = Version::matchString(str_replace(['*', 'x', 'X'], '0', substr($expression, 1)));

            if (!isset($matches[1])) {
                $group[] = new Version_Comparator('>=', '0');
                return;
            }

            $parts = explode('.', $matches[1]);
            $group[] = new Version_Comparator('>=', $matches[1]);

            if (!isset($parts[1])) {
                $group[] = new Version_Comparator('<', (int)$parts[0] + 1);
                return;
            } else {
                $group[] = new Version_Comparator('<', $parts[0] . '.' . ((int)$parts[1] + 1));
                return;
            }
        }

        if (preg_match('/^([\<\>\=]+)(.+)/i', (string)$expression, $matches)) {
            $group[] = new Version_Comparator($matches[1], $matches[2]);
            return;
        }

        $version = new Version_Comparator('=', $expression);

        if ($version->major === 'x') {
            $group[] = new Version_Comparator('>=', '0');
            return;
        } elseif ($version->minor === 'x') {
            $group[] = new Version_Comparator('>=', $version->major);
            $group[] = new Version_Comparator('<', $version->major + 1);
            return;
        } elseif ($version->patch === 'x') {
            $group[] = new Version_Comparator('>=', $version->major . '.' . $version->minor);
            $group[] = new Version_Comparator('<', $version->major . '.' . ($version->minor + 1));
            return;
        } else {
            $group[] = $version;
            return;
        }
    }


    // Match
    public function contains($version)
    {
        $version = Version::factory($version);

        foreach ($this->_groups as $group) {
            foreach ($group as $comp) {
                if (!$comp->isSatisfied($version)) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    public function maxContained(...$input)
    {
        $versions = [];

        foreach ($input as $version) {
            $versions[] = Version::factory($version);
        }

        usort($versions, function ($left, $right) {
            if ($left->eq($right)) {
                return 0;
            } elseif ($left->gt($right)) {
                return -1;
            } else {
                return 1;
            }
        });

        foreach ($versions as $version) {
            if ($this->contains($version)) {
                return $version;
            }
        }

        return null;
    }

    public function isSingleVersion()
    {
        return count($this->_groups) == 1
            && count($this->_groups[0]) == 1
            && isset($this->_groups[0][0])
            && $this->_groups[0][0]->operator == '=';
    }

    public function getSingleVersion()
    {
        if ($this->isSingleVersion()) {
            return $this->_groups[0][0];
        }
    }

    public function getMinorGroupVersion()
    {
        if (count($this->_groups) != 1) {
            return null;
        }

        $count = count($this->_groups[0]);

        if (($count > 2)
        || ($count == 2 && ($this->_groups[0][0]->operator != '>=' || $this->_groups[0][1]->operator != '<'))
        || ($count == 1 && ($this->_groups[0][0]->operator != '='))) {
            return null;
        }

        $output = new Version(
            $this->_groups[0][0]->major . '.' .
            $this->_groups[0][0]->minor
        );

        if (!$output->major && $output->minor) {
            return null;
        }

        if ($this->contains($output)) {
            return $output;
        }

        return null;
    }


    // String
    public function toString(): string
    {
        $output = [];

        foreach ($this->_groups as $group) {
            $output[] = implode(' && ', $group);
        }

        return implode(' || ', $output);
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = [];

        foreach ($this->_groups as $group) {
            $output[] = implode(' && ', $group);
        }

        yield 'values' => $output;
        yield 'showKeys' => false;
    }
}
