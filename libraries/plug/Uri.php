<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\aura as auraLib;
use df\arch;
use df\link;

class Uri implements auraLib\view\IHelper {
    
    use auraLib\view\THelper;

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

            return $this->current($from, $to);
        }
        
        if($uri instanceof arch\IRequest) {
            return $this->directory($uri, $from, $to, $asRequest);
        }
        
        if($uri instanceof core\uri\IUrl) {
            if($asRequest && $uri instanceof link\http\IUrl) {
                if($t = $uri->getDirectoryRequest()) {
                    $uri = $t;
                }
            }

            return clone $uri;
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
                    return $this->themeAsset($matches[3], null, $asRequest);

                case 'asset':
                    return $this->asset($matches[3], false, $asRequest);
            }
        }
        
        return $this->directory($uri, $from, $to, $asRequest);
    }
    
    public function current($from=null, $to=null) {
        return $this->directory($this->context->request, $from, $to);
    }

    public function mapCurrent(array $map, array $queryValues=null) {
        $request = clone $this->context->request;

        foreach($map as $key => $value) {
            switch($key) {
                case 'controller':
                    $request->setController($value);
                    break;

                case 'action':
                    $request->setAction($value);
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
        if($request === null) {
            $request = clone $this->context->request;
        } else {
            $request = arch\Request::factory($request);
        }

        $result = isset($request->query->{$key});

        if($result) {
            unset($request->query->{$key});
        } else {
            $request->query->{$key} = true;
        }

        return $this->requestToUrl($request);
    }
    
    public function directory($request, $from=null, $to=null, $asRequest=false) {
        if(!$request instanceof arch\IRequest) {
            $request = arch\Request::factory($request);
        } else {
            $request = clone $request;
        }
        
        $this->_applyRequestRedirect($request, $from, $to);

        if($asRequest) {
            return $request;
        }

        return $this->requestToUrl($request);
    }

    protected function _applyRequestRedirect(arch\Request $request, $from=null, $to=null) {
        if($from !== null) {
            if($from === true) {
                $from = $this->context->request;
            }
            
            $request->setRedirectFrom($from);
        }
        
        if($to !== null) {
            if($to === true) {
                $to = $this->context->request;
            }
            
            $request->setRedirectTo($to);
        }

        return $request;
    }

    public function asset($path, $attachment=false, $asRequest=false) {
        $request = new arch\Request('assets/download');
        $request->query->file = $path;

        if($attachment) {
            $request->query->attachment;
        }

        $output = $this->directory($request, null, null, $asRequest);
        return $this->_applyCompileTimeStamp($output);
    }

    public function themeAsset($path, $theme=null, $asRequest=false) {
        if($theme === null) {
            if($this->view) {
                $theme = $this->view->getTheme()->getId();
            } else {
                if(!$this->_defaultTheme) {
                    $config = auraLib\theme\Config::getInstance();
                    $this->_defaultTheme = $config->getThemeIdFor($this->context->location->getArea());
                }

                $theme = $this->_defaultTheme;
            }
        }

        $request = new arch\Request('theme/download?theme='.$theme);
        $request->query->file = $path;

        $output = $this->directory($request, null, null, $asRequest);
        return $this->_applyCompileTimeStamp($output);
    }

    public function back($default=null, $success=true) {
        return $this->directory($this->context->directory->backRequest($default, $success));
    }
    
    public function mailto($url) {
        return core\uri\MailtoUrl::factory($url);
    }
    
    public function http($url) {
        return link\http\Url::factory($url);
    }
    
    public function ftp($url) {
        return link\ftp\Url::factory($url);
    }

    protected function _applyCompileTimeStamp($url) {
        if(df\Launchpad::COMPILE_TIMESTAMP) {
            $url->query->cts = df\Launchpad::COMPILE_TIMESTAMP;
        } else if($this->context->application->isDevelopment() && $url->getDirectoryRequest()) {
            $url->query->cts = time();
        }

        return $url;
    }
}