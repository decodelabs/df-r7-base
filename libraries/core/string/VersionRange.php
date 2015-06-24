<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\string;

use df;
use df\core;

class VersionRange implements IVersionRange, core\IDumpable {
    
    use core\TStringProvider;

    protected $_groups = [];

    public static function factory($range) {
        if($range instanceof IRange) {
            return $range;
        }

        return new self($range);
    }

    public function __construct($range) {
        $range = preg_replace('/\s+/', ' ', $range);
        $range = str_replace(['*', 'X'], 'x', $range);
        $parts = explode(' ', $range);
        $group = [];

        while(!empty($parts)) {
            $part = array_shift($parts);

            if(in_array($part, Version_Comparator::$operators)) {
                $part .= array_shift($parts);
            }

            if($part == '-') {
                if($left = array_pop($group)) {
                    $left->operator = '>=';
                    $group[] = $left;
                }

                $part = '<='.array_shift($parts);
            }

            if($part == '||' || $part == 'or') {
                if(!empty($group)) {
                    $this->_groups[] = $group;
                    $group = [];
                }

                continue;
            } else if($part == '&&' || $part == 'and') {
                continue;
            }

            $this->_parseExpression($group, $part);
        }

        if(!empty($group)) {
            $this->_groups[] = $group;
        }
    }

    protected function _parseExpression(array &$group, $expression) {
        if(substr($expression, 0, 1) == '~') {
            $matches = Version::matchString(substr($expression, 1));

            if(!isset($matches[1])) {
                $group[] = new Version_Comparator('>=', '0');
                return;
            }

            $parts = explode('.', $matches[1]);

            if(!isset($parts[1])) {
                $group[] = new Version_Comparator('>=', $parts[0]);
                $group[] = new Version_Comparator('<', $parts[0] + 1);
                return;
            } else if(!isset($parts[2])) {
                $group[] = new Version_Comparator('>=', $parts[0].'.'.$parts[1]);
                $group[] = new Version_Comparator('<', $parts[0].'.'.($parts[1] + 1));
                return;
            } else {
                $group[] = new Version_Comparator('>=', $matches[0]);
                $group[] = new Version_Comparator('<', $parts[0].'.'.($parts[1] + 1));
                return;
            }
        }

        if(preg_match('/^([\<\>\=]+)(.+)/i', $expression, $matches)) {
            $group[] = new Version_Comparator($matches[1], $matches[2]);
            return;
        }

        $version = new Version_Comparator('=', $expression);

        if($version->major === 'x') {
            $group[] = new Version_Comparator('>=', '0');
            return;
        } else if($version->minor === 'x') {
            $group[] = new Version_Comparator('>=', $version->major);
            $group[] = new Version_Comparator('<', $version->major + 1);
            return;
        } else if($version->patch === 'x') {
            $group[] = new Version_Comparator('>=', $version->major.'.'.$version->minor);
            $group[] = new Version_Comparator('<', $version->major.'.'.($version->minor + 1));
            return;
        } else {
            $group[] = $version;
            return;
        }
    }


// Match
    public function contains($version) {
        $version = Version::factory($version);

        foreach($this->_groups as $group) {
            foreach($group as $comp) {
                if(!$comp->isSatisfied($version)) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    public function maxContained($version) {
        $versions = [];

        foreach(core\collection\Util::flattenArray(func_get_args()) as $version) {
            $versions[] = Version::factory($version);
        }

        usort($versions, function($left, $right) {
            if($left->eq($right)) {
                return 0;
            } else if($left->gt($right)) {
                return -1;
            } else {
                return 1;
            }
        });

        foreach($versions as $version) {
            if($this->contains($version)) {
                return $version;
            }
        }

        return null;
    }

    public function isSingleVersion() {
        return count($this->_groups) == 1 
            && count($this->_groups[0]) == 1
            && isset($this->_groups[0][0])
            && $this->_groups[0][0]->operator == '=';
    }

    public function getSingleVersion() {
        if($this->isSingleVersion()) {
            return $this->_groups[0][0];
        }
    }


// String
    public function toString() {
        $output = [];

        foreach($this->_groups as $group) {
            $output[] = implode(' && ', $group);
        }

        return implode(' || ', $output);
    }

// Dump
    public function getDumpProperties() {
        $output = [];

        foreach($this->_groups as $group) {
            $output[] = implode(' && ', $group);
        }

        return $output;
    }
}