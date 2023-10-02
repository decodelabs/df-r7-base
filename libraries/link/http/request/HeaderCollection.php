<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\request;

use df\core;
use df\link;

class HeaderCollection extends core\collection\HeaderMap implements link\http\IRequestHeaderCollection
{
    use link\http\THeaderCollection;

    public static function fromEnvironment()
    {
        $output = new self();

        foreach ($_SERVER as $key => $var) {
            if (substr($key, 0, 5) != 'HTTP_') {
                continue;
            }

            $output->add(substr($key, 5), $var);
        }

        return $output;
    }


    /**
     * @return $this
     */
    public function reset(): static
    {
        $this->clear();
        $this->_httpVersion = '1.1';

        return $this;
    }


    // Negotiate
    public function negotiateLanguage(string ...$options): ?string
    {
        $default = $options[0] ?? null;

        if (!isset($this['accept-language'])) {
            return $default;
        }

        $accept = $this->_parseAcceptHeader($this['accept-language']);
        $found = [];

        foreach ($accept as $lang => $params) {
            [$lang,] = explode('_', str_replace('-', '_', $lang));

            if (!isset($found[$lang])) {
                $found[$lang] = $params['q'] ?? 1;
            }
        }

        if (empty($found)) {
            return $default;
        }

        if (empty($options)) {
            return key($found);
        }

        foreach ($options as $option) {
            if (isset($found[$option])) {
                return $option;
            }
        }

        return $default;
    }

    protected function _parseAcceptHeader(string $header): array
    {
        $res = preg_match_all('/(?:[^,"]*+(?:"[^"]*+")?)+[^,"]*+/', $header, $matches);

        if (!$res) {
            return [];
        }

        $output = [];

        foreach ($matches[0] as $line) {
            $line = trim((string)$line);

            if (!strlen($line)) {
                continue;
            }

            $parts = explode(';', $line);
            $type = array_shift($parts);
            $params = [];

            foreach ($parts as $part) {
                $part = explode('=', $part, 2);
                $key = strtolower(trim($part[0]));
                $params[$key] = trim($part[1] ?? '', ' "');
            }

            $output[$type] = $params;
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield from parent::glitchDump();

        yield 'property:*httpVersion' => $this->_httpVersion;
    }
}
