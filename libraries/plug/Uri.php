<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\aura;
use df\arch;
use df\link;

class Uri implements arch\IDirectoryHelper {

    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;

    protected $_defaultTheme;

    public function requestToUrl(arch\IRequest $request) {
        return core\application\http\Router::getInstance()
            ->requestToUrl($request);
    }

    public function __invoke($uri, $from=null, $to=null, $asRequest=false) {
        if($uri === null) {
            if($asRequest) {
                return clone $this->context->request;
            }

            return $asRequest ?
                $this->currentRequest($from, $to) :
                $this->current($from, $to);
        }

        if($uri instanceof arch\IRequest) {
            return $asRequest ?
                $this->directoryRequest($uri, $from, $to) :
                $this->directory($uri, $from, $to);
        }

        if($uri instanceof core\uri\IUrl) {
            if($asRequest && $uri instanceof link\http\IUrl) {
                if($t = $uri->getDirectoryRequest()) {
                    $uri = $t;
                }
            }

            return clone $uri;
        }

        if($uri === true) {
            $uri = clone $this->context->request;
        }

        if($uri instanceof core\IStringProvider) {
            $uri = $uri->toString();
        }

        if(!is_string($uri)) {
            throw new arch\InvalidArgumentException(
                'Uri cannot be converted to a valid URL'
            );
        }

        if(substr($uri, 0, 2) == '//') {
            return new link\http\Url($uri);
        }

        if(preg_match('/^([a-z]+)\:(\/\/)?(.*)/i', $uri, $matches)) {
            switch(strtolower($matches[1])) {
                case 'http':
                case 'https':
                    return new link\http\Url($uri);

                case 'ftp':
                    return new link\ftp\Url($uri);

                case 'mailto':
                    return new core\uri\MailtoUrl($uri);

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
                    $uri = 'media/download/f'.$matches[3];
                    break;
            }
        }

        return $asRequest ?
            $this->directoryRequest($uri, $from, $to) :
            $this->directory($uri, $from, $to);
    }

    public function current($from=null, $to=null) {
        return $this->directory($this->context->request, $from, $to);
    }

    public function currentRequest($from=null, $to=null) {
        return $this->directoryRequest($this->context->request, $from, $to);
    }

    public function mapCurrent(array $map, array $queryValues=null) {
        $request = clone $this->context->request;

        foreach($map as $key => $value) {
            switch($key) {
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

        if($queryValues !== null) {
            $request->getQuery()->import($queryValues);
        }

        return $this->requestToUrl($request);
    }

    public function query(array $queryValues) {
        $request = clone $this->context->request;
        $request->getQuery()->import($queryValues);

        return $this->requestToUrl($request);
    }

    public function queryToggle($request, $key, &$result=null) {
        $request = $this->directoryRequest($request);
        $result = isset($request->query->{$key});

        if($result) {
            unset($request->query->{$key});
        } else {
            $request->query->{$key} = true;
        }

        return $this->requestToUrl($request);
    }

    public function directory($request, $from=null, $to=null) {
        return $this->requestToUrl($this->directoryRequest($request, $from, $to));
    }

    public function directoryRequest($request, $from=null, $to=null) {
        if(!$request instanceof arch\IRequest) {
            if($request === null) {
                $request = clone $this->context->request;
            } else if(is_string($request) && preg_match('#^\.\.?/#', $request)) {
                $request = $this->context->location->extractRelative($request);
                $router = core\application\http\Router::getInstance();
                $router->applyBaseMapToRelativeRequest($request);
            } else {
                $request = arch\Request::factory($request);
            }
        } else {
            $request = clone $request;
        }

        $this->_applyRequestRedirect($request, $from, $to);
        return $request;
    }

    protected function _applyRequestRedirect(arch\Request $request, $from=null, $to=null) {
        if($from !== null) {
            if($from === true) {
                $from = $this->context->request;
            }

            $request->setRedirectFrom($this->directoryRequest($from));
        }

        if($to !== null) {
            if($to === true) {
                $to = $this->context->request;
            }

            $request->setRedirectTo($this->directoryRequest($to));
        }

        return $request;
    }

// Assets
    public function asset($path, $attachment=false) {
        return $this->directory($this->assetRequest($path, $attachment));
    }

    public function assetRequest($path, $attachment=false) {
        $path = new core\uri\Url($path);
        $request = new arch\Request('assets/download?cts');
        $request->query->file = (string)$path->getPath();
        $request->query->import($path->getQuery());

        if($attachment) {
            $request->query->attachment;
        }

        return $request;
    }

    public function themeAsset($path, $theme=null) {
        return $this->directory($this->themeAssetRequest($path, $theme));
    }

    public function themeAssetRequest($path, $theme=null) {
        $theme = $this->_normalizeTheme($theme);

        $path = new core\uri\Url($path);
        $request = new arch\Request('theme/download?cts&theme='.$theme);
        $request->query->file = (string)$path->getPath();
        $request->query->import($path->getQuery());

        return $request;
    }

    public function themeDependency($name, $theme=null) {
        return $this->directory($this->themeDependencyRequest($name, $theme));
    }

    public function themeDependencyRequest($name, $themeId=null) {
        $name = rtrim($name, '/');
        $themeId = $this->_normalizeTheme($themeId);
        $theme = aura\theme\Base::factory($themeId);
        $manager = aura\theme\Manager::getInstance();
        $subPath = null;

        if(false !== strpos($name, '/')) {
            $parts = explode('/', $name);
            $name = array_shift($parts);
            $subPath = implode('/', $parts);
        }

        $dep = $manager->getInstalledDependencyFor($theme, $name);

        if(!$subPath) {
            if(isset($dep->js[0])) {
                $subPath = $dep->js[0];
            } else {
                if(!df\Launchpad::$application->isProduction()) {
                    throw new aura\theme\RuntimeException(
                        'Dependency '.$name.' does not have a main file'
                    );
                }

                $subPath = '--dependency-missing-'.$name;
            }
        }

        $path = 'vendor/'.$dep->installName.'/'.$subPath;
        return $this->assetRequest($path);
    }

    protected function _normalizeTheme($theme) {
        if($theme === null) {
            if($this->view) {
                $theme = $this->view->getTheme()->getId();
            } else {
                if(!$this->_defaultTheme) {
                    $config = aura\theme\Config::getInstance();
                    $this->_defaultTheme = $config->getThemeIdFor($this->context->location->getArea());
                }

                $theme = $this->_defaultTheme;
            }
        }

        return $theme;
    }


// Back
    public function back($default=null, $success=true, $fallback=null) {
        return $this->directory($this->backRequest($default, $success, $fallback));
    }

    public function backRequest($default=null, $success=true, $fallback) {
        $request = $this->context->request;

        if($success) {
            if(!$redirect = $request->getRedirectTo()) {
                if($default !== null) {
                    $redirect = $default;
                } else {
                    $redirect = $request->getRedirectFrom();
                }
            }


            if($redirect) {
                return $this->directoryRequest($redirect);
            }
        }

        if(!$success && ($redirect = $request->getRedirectFrom()) ){
            return $this->directoryRequest($redirect);
        }

        if($default !== null) {
            return $this->directoryRequest($default);
        }

        if($fallback !== null) {
            return $this->directoryRequest($fallback);
        }


        if($default === null) {
            $default = $request->getParent();
        } else if(!$default instanceof arch\IRequest) {
            $default = $this->directoryRequest($default);
        } else {
            $default = clone $default;
        }

        return $default;
    }


// URLs
    public function mailto($url) {
        return core\uri\MailtoUrl::factory($url);
    }

    public function http($url) {
        return link\http\Url::factory($url);
    }

    public function ftp($url) {
        return link\ftp\Url::factory($url);
    }
}