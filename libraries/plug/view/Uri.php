<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\aura;
use df\arch;
use df\halo;

class Uri implements aura\view\IHelper {
    
    protected $_view;
    
    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
    }
    
    public function to($uri, $from=null, $to=null) {
        if($uri === null) {
            return $this->current($from, $to);
        }
        
        if($uri instanceof arch\IRequest) {
            return $this->request($uri, $from, $to);
        }
        
        if($uri instanceof core\uri\IUrl) {
            return clone $uri;
        }
        
        if($uri instanceof core\IStringProvider) {
            $uri = $uri->toString();
        }
        
        if(!is_string($uri)) {
            throw new aura\view\InvalidArgumentException(
                'Uri cannot be converted to a valid URL'
            );
        }
        
        if(preg_match('/^([a-z]+)\:/i', $uri, $matches)) {
            switch(strtolower($matches[1])) {
                case 'http':
                case 'https':
                    return new halo\protocol\http\Url($uri);
                    
                case 'ftp':
                    return new halo\protocol\ftp\Url($uri);
                    
                case 'mailto':
                    return new core\uri\MailtoUrl($uri);
            }
        }
        
        return $this->request($uri, $from, $to);
    }
    
    public function current($from=null, $to=null) {
        return $this->request($this->_view->getContext()->getRequest(), $from, $to);
    }
    
    public function request($request, $from=null, $to=null) {
        if(!$request instanceof arch\IRequest) {
            $request = arch\Request::factory($request);
        } else {
            $request = clone $request;
        }
        
        
        if($from !== null) {
            if($from === true) {
                $from = $this->_view->getContext()->getRequest();
            }
            
            $request->setRedirectFrom($from);
        }
        
        if($to !== null) {
            if($to === true) {
                $to = $this->_view->getContext()->getRequest();
            }
            
            $request->setRedirectTo($to);
        }
        
        return $this->_view->getContext()->getApplication()->requestToUrl($request);
    }

    public function back($default=null, $success=true) {
        $request = $this->_view->getContext()->getRequest();
        
        if($success && ($redirect = $request->getRedirectTo())) {
            return $this->request($redirect);
        } else if((!$success || ($success && !$request->getRedirectTo()))
            && ($redirect = $request->getRedirectFrom())) {
            return $this->request($redirect);
        }
            
        if($default === null) {
            $default = $request->getParent();
        }
        
        return $this->request($default);
    }
    
    public function mailto($url) {
        return core\uri\MailtoUrl::factory($url);
    }
    
    public function http($url) {
        return halo\protocol\http\Url::factory($url);
    }
    
    public function ftp($url) {
        return halo\protocol\ftp\Url::factory($url);
    }
}