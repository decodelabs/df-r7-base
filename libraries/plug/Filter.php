<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\arch;
use df\flex;
use df\flow;
use df\link;

class Filter implements arch\IDirectoryHelper, \ArrayAccess {

    use arch\TDirectoryHelper;

    public function __invoke($value, string $type, ...$args) {
        if($nullable = substr($type, 0, 1) == '?') {
            $type = substr($type, 1);
        }

        $output = $this->{$type}($value, ...$args);

        if(!$nullable && $output === null) {
            throw core\Error::{'core/filter/EValue,EBadRequest'}([
                'message' => 'Empty '.$type.' filter value',
                'http' => 400
            ]);
        }

        return $output;
    }


// Query access
    public function offsetSet($key, $value) {}

    public function offsetGet($key): Callable {
        if($nullable = substr($key, 0, 1) == '?') {
            $key = substr($key, 1);
        }

        $value = $this->context->request[$key];

        return new class($this, $key, $value, $nullable) {

            public $value;
            public $nullable = false;

            private $_key;
            private $_filter;

            public function __construct(Filter $filter, string $key, $value, bool $nullable=false) {
                $this->_filter = $filter;
                $this->_key = $key;
                $this->value = $value;
                $this->nullable = $nullable;
            }

            public function __invoke(string $type, ...$args) {
                $output = $this->_filter->{$type}($this->value, ...$args);

                if(!$this->nullable && $output === null) {
                    throw core\Error::{'core/filter/EValue,EBadRequest'}([
                        'message' => 'Query var '.$this->_key.' did not contain a valid '.$type,
                        'namespace' => __NAMESPACE__,
                        'http' => 400
                    ]);
                }

                return $output;
            }

            public function __call($type, $args) {
                return $this->__invoke($type, ...$args);
            }
        };
    }

    public function offsetExists($key): bool {
        return $this->context->request->offsetExists($key);
    }

    public function offsetUnset($key) {}

    public function query($key, string $type, ...$args) {
        if($nullable = substr($key, 0, 1) == '?') {
            $key = substr($key, 1);
            $type = '?'.ltrim($type, '?');
        }

        return $this->__invoke($this->context->request[$key], $type, ...$args);
    }


// Boolean
    public function bool($value, array $options=[]) {//: ?bool {
        return $this->_applyFilter($value, FILTER_VALIDATE_BOOLEAN, [
            'default' => $options['default'] ?? null
        ], FILTER_NULL_ON_FAILURE);
    }


// Numbers
    public function int($value, array $options=[]) {//: ?int {
        $value = $this->_applyFilter($value, FILTER_SANITIZE_NUMBER_INT);

        return $this->_applyFilter($value, FILTER_VALIDATE_INT, [
            'default' => $options['default'] ?? null,
            'min_range' => $options['min'] ?? null,
            'max_range' => $options['max'] ?? null
        ]);
    }

    public function float($value, array $options=[]) {//: ?float {
        $value = $this->_applyFilter($value, FILTER_SANITIZE_NUMBER_FLOAT, [], FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);

        $value = $this->_applyFilter($value, FILTER_VALIDATE_FLOAT, [
            'default' => $options['default'] ?? null
        ], FILTER_FLAG_ALLOW_THOUSAND);

        $this->_check($value, $options['min'] ?? null, function($value, $min) {
            return $value >= $min;
        });

        $this->_check($value, $options['max'] ?? null, function($value, $max) {
            return $value <= $max;
        });

        return $value;
    }



// Basic strings
    public function string($value, array $options=[]) {//: ?string {
        $flags = 0;

        if($options['noEncodeQuotes'] ?? false) $flags |= FILTER_FLAG_NO_ENCODE_QUOTES;
        if($options['stripLow'] ?? false) $flags |= FILTER_FLAG_STRIP_LOW;
        if($options['stripHigh'] ?? false) $flags |= FILTER_FLAG_STRIP_HIGH;
        if($options['stripBacktick'] ?? false) $flags |= FILTER_FLAG_STRIP_BACKTICK;
        if($options['encodeLow'] ?? false) $flags |= FILTER_FLAG_ENCODE_LOW;
        if($options['encodeHigh'] ?? false) $flags |= FILTER_FLAG_ENCODE_HIGH;
        if($options['encodeAmp'] ?? false) $flags |= FILTER_FLAG_ENCODE_AMP;

        return $this->_applyFilter($value, FILTER_SANITIZE_STRING, [
            'default' => $options['default'] ?? null
        ], $flags);
    }



// Email
    public function email($value, array $options=[]) {//: ?string {
        $value = flow\mail\Address::factory($value);
        $value->setAddress($this->_applyFilter($value->getAddress(), FILTER_SANITIZE_EMAIL));

        if($value->isValid()) {
            return $value->getAddress();
        } else {
            return $options['default'] ?? null;
        }
    }


// Url
    public function url($value, array $options=[]) {//: ?link\http\IUrl {
        $value = $this->_applyFilter($value, FILTER_SANITIZE_URL);

        $flags = 0;

        if($options['schemeRequired'] ?? false) $flags |= FILTER_FLAG_SCHEME_REQUIRED;
        if($options['hostRequired'] ?? false) $flags |= FILTER_FLAG_HOST_REQUIRED;
        if($options['pathRequired'] ?? false) $flags |= FILTER_FLAG_PATH_REQUIRED;
        if($options['queryRequired'] ?? false) $flags |= FILTER_FLAG_QUERY_REQUIRED;

        $value = $this->_applyFilter($value, FILTER_VALIDATE_URL, [
            'default' => $options['default'] ?? null
        ]);

        if($value === null) {
            return null;
        }

        $value = link\http\Url::factory($value);

        if(($options['httpsRequired'] ?? false) && !$value->isSecure()) return null;

        if(isset($options['tld'])) {
            $domain = $value->getDomain();
            $tldFound = false;

            foreach((array)$options['tld'] as $tld) {
                $tld = '.'.ltrim($tld);

                if(substr($domain, -strlen($tld)) === $tld) {
                    $tldFound = true;
                    break;
                }
            }

            if(!$tldFound) {
                return null;
            }
        }

        return $value;
    }




// Ids
    public function slug($value, array $options=[]) {//: ?string {
        if(empty($value)) {
            $value = $options['default'] ?? null;
        }

        if($value === null) {
            return $value;
        }

        if(empty($output = flex\Text::formatSlug($value))) {
            $output = null;
        }

        return $output;
    }

    public function intId($value, array $options=[]) {//: ?int {
        return $this->int($value, [
            'default' => $options['default'] ?? null,
            'min' => 1
        ]);
    }

    public function guid($value, array $options=[]) {//: ?flex\IGuid {
        try {
            $value = flex\Guid::factory($value);
        } catch(\Throwable $e) {
            if(null !== ($value = ($options['default'] ?? null))) {
                $value = flex\Guid::factory($value);
            }
        }

        return $value;
    }




// Helpers
    protected function _applyFilter($value, $filter, array $options=[], int $flags=0) {
        foreach($options as $key => $option) {
            if($option === null) {
                unset($options[$key]);
            }
        }

        return filter_var($value, $filter, [
            'options' => array_merge([
                'default' => $options['default'] ?? null
            ], $options),
            'flags' => $flags
        ]);
    }

    protected function _check(&$value, $option, Callable $callback) {
        if($option === null) {
            return;
        }

        if(!$callback($value, $option)) {
            $value = null;
        }
    }
}