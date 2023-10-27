<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Compass\Range;
use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use df\link\http\request\Base as RequestBase;
use df\link\http\Url;

class Http implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        return [
            'baseUrl' => [
                'development' => null,
                'testing' => null,
                'production' => null
            ],
            'sendFileHeader' => 'X-Sendfile',
            'secure' => false,
            'ipRanges' => null,
            'ipRangeAreas' => null,
            'credential' => []
        ];
    }


    public function getRootUrl(
        ?string $envMode = null
    ): string {
        if (!isset($this->data->baseUrl)) {
            throw Exceptional::Config(
                'No base url set'
            );
        }

        if ($envMode === null) {
            $envMode = Genesis::$environment->getMode();
        }

        $output = null;

        if (isset($this->data->baseUrl[$envMode])) {
            $output = $this->data->baseUrl[$envMode];
        } elseif (isset($this->data->baseUrl->{$envMode}->{'*'})) {
            $output = $this->data->baseUrl->{$envMode}['*'];
        } elseif (isset($this->data->baseUrl->{$envMode}->{0})) {
            $output = $this->data->baseUrl->{$envMode}[0];
        } elseif (isset($this->data->baseUrl->{$envMode}->{'front'})) {
            $output = $this->data->baseUrl->{$envMode}['front'];
        }

        if ($output === null) {
            if (!isset($_SERVER['HTTP_HOST'])) {
                throw Exceptional::Config(
                    'No base url set for ' . $envMode . ' mode'
                );
            }

            $output = $this->normalizeUrl(static::generateRootUrl());
        }

        return trim((string)$output, '/');
    }

    protected function normalizeUrl(string $url): string
    {
        $url = Url::factory($url);
        $url->getPath()->shouldAddTrailingSlash(true)->isAbsolute(true);
        $domain = $url->getDomain();
        $port = $url->getPort();

        if (!empty($port) && $port != '80') {
            $domain = $domain . ':' . $port;
        }

        return $domain . $url->getPathString();
    }

    /**
     * @return array<string, string>
     */
    public function getBaseUrlMap(
        ?string $envMode = null
    ): array {
        if ($envMode === null) {
            $envMode = Genesis::$environment->getMode();
        }

        if (
            !isset($this->data->baseUrl->{$envMode}) &&
            isset($_SERVER['HTTP_HOST'])
        ) {
            return [
                '*' => $this->normalizeUrl(static::generateRootUrl())
            ];
        }

        $node = $this->data->baseUrl->{$envMode};
        $output = [];

        if ($node->hasValue()) {
            $output['*'] = trim((string)$node->getValue(), '/');
        }

        foreach ($node as $key => $value) {
            $output[(string)$key] = trim((string)$value, '/');
        }

        return $output;
    }

    protected static function generateRootUrl(): string
    {
        $baseUrl = null;
        $request = new RequestBase(true);
        $host = $request->getUrl()->getHost();
        $path = $request->getUrl()->getPathString();

        $baseUrl = $host . '/' . trim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
        $currentUrl = $host . '/' . $path;

        if (substr($currentUrl, 0, strlen($baseUrl)) != $baseUrl) {
            $parts = explode('/', $currentUrl);
            array_pop($parts);
            $baseUrl = implode('/', $parts) . '/';
        }

        return $baseUrl;
    }



    public function getSendFileHeader(): ?string
    {
        return $this->data->sendFileHeader->as('?string');
    }


    public function isSecure(): bool
    {
        return $this->data->secure->as('bool');
    }


    /**
     * @return array<Range>
     */
    public function getIpRanges(): array
    {
        $output = [];

        foreach ($this->data->ipRanges as $range) {
            $output[] = Range::parse((string)$range);
        }

        return $output;
    }


    /**
     * @return array<string, string>|null
     */
    public function getCredentials(?string $mode = null): ?array
    {
        if ($mode === null) {
            $mode = Genesis::$environment->getMode();
        }

        if (isset($this->data->credentials->{$mode}['username'])) {
            $set = $this->data->credentials->{$mode};
        } elseif (isset($this->data->credentials['username'])) {
            $set = $this->data->credentials;
        } else {
            return null;
        }

        return [
            'username' => $set->username->as('string'),
            'password' => $set->password->as('?string')
        ];
    }
}
