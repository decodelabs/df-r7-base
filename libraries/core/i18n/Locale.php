<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df\core;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Locale implements ILocale, \Serializable, Dumpable
{
    protected $_language;
    protected $_script;
    protected $_region;
    protected $_variants = [];
    protected $_keywords = [];

    public static function factory($locale)
    {
        if ($locale instanceof ILocale) {
            return $locale;
        }

        if ($locale === null || $locale === true) {
            return Manager::getInstance()->getLocale();
        }

        return new self($locale);
    }

    public static function setCurrent($locale)
    {
        return Manager::getInstance()->setLocale($locale);
    }

    public static function getCurrent()
    {
        return Manager::getInstance()->getLocale();
    }

    public function __construct($locale)
    {
        $parts = \Locale::parseLocale((string)$locale);

        foreach ($parts as $key => $part) {
            if ($key == 'language') {
                $this->_language = $part;
            } elseif ($key == 'script') {
                $this->_script = $part;
            } elseif ($key == 'region') {
                $this->_region = $part;
            } elseif (substr($key, 0, 7) == 'variant') {
                $this->_variants[] = $part;
            }
        }

        if ($keywords = \Locale::getKeywords((string)$locale)) {
            $this->_keywords = $keywords;
        }
    }


    // Serialize
    public function serialize()
    {
        return $this->toString();
    }

    public function unserialize($data)
    {
        $this->__construct($data);
    }


    // Accessors
    public function toString(): string
    {
        try {
            $values = ['language' => $this->_language];

            if ($this->_region !== null) {
                $values['region'] = $this->_region;
            }

            if ($this->_script !== null) {
                $values['script'] = $this->_script;
            }

            if (!empty($this->_variants)) {
                $values['variant'] = $this->_variants;
            }

            $output = \Locale::composeLocale($values);
        } catch (\Throwable $e) {
            return $this->_language.'_'.$this->_region;
        }

        if (!empty($this->_keywords)) {
            $keywords = [];

            foreach ($this->_keywords as $key => $value) {
                $keywords[] = $key.'='.$value;
            }

            $output .= '@'.implode(';', $keywords);
        }

        return $output;
    }

    public function __toString(): string
    {
        try {
            return (string)$this->toString();
        } catch (\Throwable $e) {
            return $this->_language.'_'.$this->_region;
        }
    }

    public function getDisplayName($formatLocale=null)
    {
        if ($formatLocale === true) {
            $formatLocale = $this;
        }

        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayName((string)$this, (string)$formatLocale);
    }


    public function getLanguage()
    {
        return $this->_language;
    }

    public function getDisplayLanguage($formatLocale=null)
    {
        if ($formatLocale === true) {
            $formatLocale = $this;
        }

        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayLanguage((string)$this, (string)$formatLocale);
    }

    public function getScript()
    {
        return $this->_script;
    }

    public function getDisplayScript($formatLocale=null)
    {
        if ($formatLocale === true) {
            $formatLocale = $this;
        }

        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayScript((string)$this, (string)$formatLocale);
    }

    public function getRegion()
    {
        return $this->_region;
    }

    public function getDisplayRegion($formatLocale=null)
    {
        if ($formatLocale === true) {
            $formatLocale = $this;
        }

        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayRegion((string)$this, (string)$formatLocale);
    }

    public function getVariants()
    {
        return $this->_variants;
    }

    public function getDisplayVariants($formatLocale=null)
    {
        if ($formatLocale === true) {
            $formatLocale = $this;
        }

        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayVariant((string)$this, (string)$formatLocale);
    }

    public function getKeywords()
    {
        return $this->_keywords;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->__toString();
    }
}
