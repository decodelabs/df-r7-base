<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use DecodeLabs\Atlas\File;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Hydro;
use DecodeLabs\R7\Config\Http as HttpConfig;
use DecodeLabs\R7\Legacy;
use df\arch;
use df\aura;
use df\core;
use df\fuse;
use df\link;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;
use Stringable;

class Uri implements arch\IDirectoryHelper
{
    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;

    protected $_defaultTheme;

    public function requestToUrl(arch\Request $request)
    {
        return Legacy::$http->getRouter()
            ->requestToUrl($request);
    }

    public function __invoke($uri, $from = null, $to = null, $asRequest = false)
    {
        if ($uri === null) {
            return $asRequest ?
                $this->currentRequest($from, $to) :
                $this->current($from, $to);
        }

        if ($uri instanceof arch\IRequest) {
            return $asRequest ?
                $this->directoryRequest($uri, $from, $to) :
                $this->directory($uri, $from, $to);
        }

        if ($uri instanceof core\uri\IUrl) {
            if ($asRequest && $uri instanceof link\http\IUrl) {
                if ($t = $uri->getDirectoryRequest()) {
                    $uri = $t;
                }
            }

            return clone $uri;
        }

        if ($uri === true) {
            $uri = clone $this->context->request;
        }

        if ($uri instanceof Stringable) {
            $uri = $uri->__toString();
        }

        if (!is_string($uri)) {
            throw Exceptional::InvalidArgument(
                'Uri cannot be converted to a valid URL'
            );
        }

        if (substr($uri, 0, 2) == '//') {
            return new link\http\Url($uri);
        }

        if (preg_match('/^([a-z]+)\:(\/\/)?(.*)/i', $uri, $matches)) {
            switch (strtolower($matches[1])) {
                case 'http':
                case 'https':
                    return new link\http\Url($uri);

                case 'mailto':
                    return new core\uri\MailtoUrl($uri);

                case 'tel':
                    return new core\uri\TelephoneUrl($uri);

                case 'theme':
                    return $asRequest ?
                        $this->themeAssetRequest($matches[3]) :
                        $this->themeAsset($matches[3]);

                case 'dependency':
                    return $asRequest ?
                        $this->themeDependencyRequest($matches[3]) :
                        $this->themeDependency($matches[3]);

                case 'asset':
                    return $asRequest ?
                        $this->assetRequest($matches[3]) :
                        $this->asset($matches[3]);

                case 'media':
                    $uri = 'media/download/f' . $matches[3];
                    break;

                case 'data':
                    return $uri;
            }
        }

        return $asRequest ?
            $this->directoryRequest($uri, $from, $to) :
            $this->directory($uri, $from, $to);
    }

    public function current($from = null, $to = null)
    {
        return $this->directory($this->context->request, $from, $to);
    }

    public function currentRequest($from = null, $to = null)
    {
        return $this->directoryRequest($this->context->request, $from, $to);
    }

    public function mapCurrent(array $map, array $queryValues = null)
    {
        $request = clone $this->context->request;

        foreach ($map as $key => $value) {
            switch ($key) {
                case 'controller':
                    $request->setController($value);
                    break;

                case 'node':
                    $request->setNode($value);
                    break;

                case 'type':
                    $request->setType($value);
                    break;

                case 'fileName':
                    $request->setFileName($value);
                    break;

                case 'query':
                    $request->setQuery($value);
                    break;
            }
        }

        if ($queryValues !== null) {
            $request->getQuery()->import($queryValues);
        }

        return $this->requestToUrl($request);
    }

    public function query(array $queryValues)
    {
        $request = clone $this->context->request;
        $request->getQuery()->import($queryValues);

        return $this->requestToUrl($request);
    }

    public function queryToggle($request, $key, &$result = null)
    {
        $request = $this->directoryRequest($request);
        $result = isset($request->query->{$key});

        if ($result) {
            unset($request->query->{$key});
        } else {
            $request->query->{$key} = true;
        }

        return $this->requestToUrl($request);
    }

    public function directory($request, $from = null, $to = null)
    {
        return $this->requestToUrl($this->directoryRequest($request, $from, $to));
    }

    public function directoryRequest($request, $from = null, $to = null)
    {
        if (!$request instanceof arch\IRequest) {
            if ($request === null) {
                $request = clone $this->context->request;
            } elseif (is_string($request) && preg_match('#^\.\.?/#', (string)$request)) {
                $request = $this->context->location->extractRelative($request);
                $router = Legacy::$http->getRouter();
                $router->applyBaseMapToRelativeRequest($request);
            } else {
                $request = arch\Request::factory($request);
            }
        } else {
            $request = clone $request;
        }

        $request->normalize();
        $this->_applyRequestRedirect($request, $from, $to);
        return $request;
    }

    protected function _applyRequestRedirect(arch\Request $request, $from = null, $to = null)
    {
        if ($from !== null) {
            if ($from === true) {
                $from = $this->context->request;
            }

            $request->setRedirectFrom($from);
        }

        if ($to !== null) {
            if ($to === true) {
                $to = $this->context->request;
            }

            $request->setRedirectTo($to);
        }

        return $request;
    }

    // Assets
    public function asset($path, $attachment = false)
    {
        return $this->directory($this->assetRequest($path, $attachment));
    }

    public function assetRequest($path, $attachment = false)
    {
        $path = new core\uri\Url($path);
        $request = new arch\Request('assets/download?cts');
        $request->query->file = (string)$path->getPath();
        $request->query->import($path->getQuery());

        if ($attachment) {
            $request->query->attachment = null;
        }

        return $request;
    }

    public function themeAsset($path, $theme = null)
    {
        return $this->directory($this->themeAssetRequest($path, $theme));
    }

    public function themeAssetRequest($path, $theme = null)
    {
        $theme = $this->_normalizeTheme($theme, $path);

        $path = new core\uri\Url($path);
        $request = new arch\Request('theme/download?cts&theme=' . $theme);
        $request->query->file = (string)$path->getPath();
        $request->query->import($path->getQuery());

        return $request;
    }

    public function themeDependency($name, $theme = null)
    {
        return $this->directory($this->themeDependencyRequest($name, $theme));
    }

    public function themeDependencyRequest($name, $themeId = null)
    {
        $name = rtrim((string)$name, '/');
        $themeId = $this->_normalizeTheme($themeId, $name);
        $theme = aura\theme\Base::factory($themeId);
        $manager = fuse\Manager::getInstance();
        $subPath = null;

        if (false !== strpos($name, '/')) {
            $parts = explode('/', $name);
            $name = array_shift($parts);
            $subPath = implode('/', $parts);
        }

        $dep = $manager->getInstalledDependencyFor($theme, $name);

        if (!$subPath) {
            if (isset($dep->js[0])) {
                $subPath = $dep->js[0];
            } else {
                if (!Genesis::$environment->isProduction()) {
                    throw Exceptional::{'df/fuse/Runtime'}(
                        'Dependency ' . $name . ' does not have a main file'
                    );
                }

                $subPath = '--dependency-missing-' . $name;
            }
        }

        $path = 'vendor/' . $dep->installName . '/' . $subPath;
        return $this->assetRequest($path);
    }

    protected function _normalizeTheme($theme, &$input = null)
    {
        if ($theme !== null) {
            return $theme;
        }

        if ($input !== null) {
            $theme = $this->context->extractThemeId($input);

            if ($theme !== null) {
                return $theme;
            }
        }

        if ($this->view) {
            return $this->view->getTheme()->getId();
        } else {
            if (!$this->_defaultTheme) {
                $this->_defaultTheme = Legacy::getThemeIdFor(
                    $this->context->location->getArea()
                );
            }

            return $this->_defaultTheme;
        }
    }


    // Back
    public function back($default = null, $success = true, $fallback = null)
    {
        return $this->directory($this->backRequest($default, $success, $fallback));
    }

    public function backRequest($default = null, $success = true, $fallback = null)
    {
        $request = $this->context->request;

        if ($success) {
            if (!$redirect = $request->getRedirectTo()) {
                if ($default !== null) {
                    $redirect = $default;
                } else {
                    $redirect = $request->getRedirectFrom();
                }
            }


            if ($redirect) {
                return $this->directoryRequest($redirect);
            }
        }

        if (!$success && ($redirect = $request->getRedirectFrom())) {
            return $this->directoryRequest($redirect);
        }

        if ($default !== null) {
            return $this->directoryRequest($default);
        }

        if ($fallback !== null) {
            return $this->directoryRequest($fallback);
        }

        return $request->getParent();
    }


    // URLs
    public function mailto($url)
    {
        return core\uri\MailtoUrl::factory($url);
    }

    public function http($url)
    {
        return link\http\Url::factory($url);
    }


    // Data
    public function data($url): string
    {
        $response = $this->_getDataResponse($url);
        $type = $response->getHeaderLine('Content-Type');

        return 'data:' . $type . ';base64,' . base64_encode((string)$response->getBody());
    }

    public function fetch($url): File
    {
        return Hydro::responseToMemoryFile(
            $this->_getDataResponse($url)
        );
    }

    protected function _getDataResponse($url): ResponseInterface
    {
        $url = $this->__invoke($url, null, null, true);
        $httpClient = new HttpClient();

        if ($url instanceof arch\IRequest) {
            $url = $this->__invoke($url);
            $config = HttpConfig::load();
            $credentials = $config->getCredentials(Genesis::$environment->getMode());

            $options = [
                'verify' => false,
                'auth' => $credentials
            ];
        } else {
            $options = [];
        }

        try {
            return $httpClient->get((string)$url, $options);
        } catch (\Exception $e) {
            throw Exceptional::NotFound([
                'message' => 'File not loadable: ' . $url,
            ]);
        }
    }
}
