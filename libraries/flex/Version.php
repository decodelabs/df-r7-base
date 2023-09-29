<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class Version implements core\IStringProvider, Dumpable
{
    use core\TStringProvider;

    public const REGEX = '/^(?<version>[0-9.x]+)(?<prerelease>-?[0-9a-zA-Z.]+)?(?<build>\+[0-9a-zA-Z.]+)?$/';
    public const GREEK = ['pre-alpha', 'alpha', 'pre-beta', 'beta', 'pre-rc', 'rc'];

    public $major = 0;
    public $minor = 0;
    public $patch = 0;
    public $preRelease = null;
    public $build = null;

    public static function factory($version)
    {
        if ($version instanceof Version) {
            return $version;
        }

        return new self($version);
    }

    public static function matchString($version)
    {
        if (!preg_match(self::REGEX, $version, $matches)) {
            throw Exceptional::Runtime('Invalid version: ' . $version);
        }

        return $matches;
    }

    public function __construct($version)
    {
        $matches = self::matchString($version);
        $parts = explode('.', $matches[1]);
        $this->major = array_shift($parts);
        $this->minor = array_shift($parts);
        $this->patch = array_shift($parts);

        if ($this->major != 'x') {
            $this->major = (int)$this->major;
        }
        if ($this->minor != 'x') {
            $this->minor = (int)$this->minor;
        }
        if ($this->patch != 'x') {
            $this->patch = (int)$this->patch;
        }

        if (isset($matches[2])) {
            $this->preRelease = explode('.', ltrim($matches[2], '-'));
        }

        if (isset($matches[3])) {
            $this->build = explode('.', ltrim($matches[3], '+'));
        }
    }

    // Members
    public function setMajor($major)
    {
        $this->major = $major;
        return $this;
    }

    public function getMajor()
    {
        return $this->major;
    }

    public function setMinor($minor)
    {
        $this->minor = $minor;
        return $this;
    }

    public function getMinor()
    {
        return $this->minor;
    }

    public function setPatch($patch)
    {
        $this->patch = $patch;
        return $this;
    }

    public function getPatch()
    {
        return $this->patch;
    }

    public function setPreRelease($preRelease)
    {
        if (!is_array($preRelease)) {
            if (strlen($preRelease)) {
                $preRelease = explode('.', $preRelease);
            } else {
                $preRelease = null;
            }
        }

        if (empty($preRelease)) {
            $preRelease = null;
        }

        $this->preRelease = $preRelease;
        return $this;
    }

    public function getPreRelease()
    {
        return $this->preRelease;
    }

    public function getPreReleaseString()
    {
        if (is_array($this->preRelease)) {
            return implode('.', $this->preRelease);
        }
    }

    public function setBuild($build)
    {
        if (!is_array($build)) {
            if (strlen($build)) {
                $build = explode('.', $build);
            } else {
                $build = null;
            }
        }

        if (empty($build)) {
            $build = null;
        }

        $this->build = $build;
        return $this;
    }

    public function getBuild()
    {
        return $this->build;
    }

    public function getBuildString()
    {
        if (is_array($this->build)) {
            return implode('.', $this->build);
        }
    }


    // Comparison
    public function eq($version)
    {
        $version = self::factory($version);

        if ($this->major != $version->major
        || $this->minor != $version->minor
        || $this->patch != $version->patch) {
            return false;
        }

        if (!$this->_matchPreRelease($version)) {
            return false;
        }

        if (!$this->_matchBuild($version)) {
            return false;
        }

        return true;
    }

    public function matches($version)
    {
        $version = self::factory($version);

        if (!$this->_matchMain($version)) {
            return false;
        }

        if ($version->preRelease && !$this->_matchPreRelease($version)) {
            return false;
        }

        if ($version->build && !$this->_matchBuild($version)) {
            return false;
        }

        return true;
    }

    public function gt($version)
    {
        return !$this->lte($version);
    }

    public function gte($version)
    {
        return !$this->lt($version);
    }

    public function lt($version)
    {
        $version = self::factory($version);

        if ($this->eq($version)) {
            return false;
        }

        return $this->lte($version);
    }

    public function lte($version)
    {
        $version = self::factory($version);

        if (!$this->_matchMain($version)) {
            return $this->_mainLt($version);
        }

        if (!$this->preRelease && $version->preRelease) {
            return false;
        } elseif ($this->preRelease && !$version->preRelease) {
            return true;
        } elseif ($this->preRelease && $version->preRelease) {
            if (!$this->_matchPreRelease($version)) {
                return $this->_preReleaseLt($version);
            }
        }

        if (!$this->build && $version->build) {
            return true;
        } elseif ($this->build && !$version->build) {
            return false;
        } elseif ($this->build && $version->build) {
            if (!$this->_matchBuild($version)) {
                return $this->_buildLt($version);
            }
        }

        return true;
    }

    protected function _matchMain(Version $version)
    {
        if ($this->major != $version->major
        || $this->minor != $version->minor
        || $this->patch != $version->patch) {
            return false;
        }

        return true;
    }

    protected function _mainLt(Version $version)
    {
        if ($this->major > $version->major) {
            return false;
        }
        if ($this->major < $version->major) {
            return true;
        }
        if ($this->minor > $version->minor) {
            return false;
        }
        if ($this->minor < $version->minor) {
            return true;
        }
        if ($this->patch > $version->patch) {
            return false;
        }
        if ($this->patch < $version->patch) {
            return true;
        }
    }

    protected function _matchPreRelease(Version $version)
    {
        if ($this->preRelease) {
            $leftPreCount = count($this->preRelease);
        } else {
            $leftPreCount = 0;
        }
        if ($version->preRelease) {
            $rightPreCount = count($version->preRelease);
        } else {
            $rightPreCount = 0;
        }

        if ($leftPreCount != $rightPreCount) {
            return false;
        }

        if ($leftPreCount) {
            foreach ($this->preRelease as $i => $value) {
                if (!isset($version->preRelease[$i]) || $version->preRelease[$i] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function _preReleaseLt(Version $version)
    {
        foreach ($this->preRelease as $i => $left) {
            if (!isset($version->preRelease[$i])) {
                return false;
            }

            $right = $version->preRelease[$i];

            if ($left != $right) {
                if ($leftGreek = in_array(strtolower((string)$left), self::GREEK)) {
                    $left = strtolower((string)$left);
                }

                if ($rightGreek = in_array(strtolower((string)$right), self::GREEK)) {
                    $right = strtolower((string)$right);
                }

                if ($leftGreek && !$rightGreek) {
                    return false;
                } elseif ($rightGreek && !$leftGreek) {
                    return true;
                } elseif ($leftGreek && $rightGreek) {
                    $leftScore = array_search($left, self::GREEK);
                    $rightScore = array_search($right, self::GREEK);

                    if ($leftScore > $rightScore) {
                        return false;
                    } elseif ($leftScore < $rightScore) {
                        return true;
                    }
                } else {
                    if ($left > $right) {
                        return false;
                    } elseif ($left < $right) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function _matchBuild(Version $version)
    {
        if ($this->build) {
            $leftBuildCount = count($this->build);
        } else {
            $leftBuildCount = 0;
        }
        if ($version->build) {
            $rightBuildCount = count($version->build);
        } else {
            $rightBuildCount = 0;
        }

        if ($leftBuildCount != $rightBuildCount) {
            return false;
        }

        if ($leftBuildCount) {
            foreach ($this->build as $i => $value) {
                if (!isset($version->build[$i]) || $version->build[$i] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function _buildLt(Version $version)
    {
        foreach ($this->build as $i => $left) {
            if (!isset($version->build[$i])) {
                return false;
            }

            $right = $version->build[$i];

            if ($left != $right) {
                if ($left > $right) {
                    return false;
                } elseif ($left < $right) {
                    return true;
                }
            }
        }

        return false;
    }


    // Range
    public function isInRange($range)
    {
        return VersionRange::factory($range)->contains($this);
    }



    // String
    public function toString(): string
    {
        $output = sprintf(
            '%d.%d.%d',
            $this->major,
            $this->minor,
            $this->patch
        );

        if ($this->preRelease) {
            $output .= '-' . $this->getPreReleaseString();
        }

        if ($this->build) {
            $output .= '+' . $this->getBuildString();
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}


class Version_Comparator extends Version
{
    public const OPERATORS = ['<', '>', '<=', '>=', '<>', '='];

    public $operator;

    public function __construct($operator, $version)
    {
        $this->operator = $operator;
        parent::__construct($version);
    }

    public function isSatisfied(Version $version)
    {
        switch ($this->operator) {
            case '<':
                return $version->lt($this);

            case '>':
                return $version->gt($this);

            case '<=':
                return $version->lte($this);

            case '>=':
                return $version->gte($this);

            case '<>':
                return !$version->eq($this);

            case '=':
            default:
                return $version->eq($this);
        }
    }

    public function toString(): string
    {
        return $this->operator . ' ' . parent::toString();
    }
}
