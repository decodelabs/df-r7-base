<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;
use df\halo;

class Base implements IView {
    
    use arch\TContextAware;
    use core\THelperProvider;
    use core\string\THtmlStringEscapeHandler;
    
    protected $_headers;
    protected $_cookies;
    protected $_contentProvider;
    protected $_renderedContent = null;
    
    public static function factory($type, arch\IContext $context) {
        $type = ucfirst($type);
        $class = 'df\\aura\\view\\'.$type;
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }
        
        return new $class($type, $context);
    }
    
    public function __construct($type, arch\IContext $context) {
        $this->_type = $type;
        $this->_context = $context;
    }
    
    
// Response
    public function isOk() {
        return true;
    }

    public function getHeaders() {
        if(!$this->_headers) {
            $this->_headers = new halo\protocol\http\response\HeaderCollection();
        }
        
        return $this->_headers;
    }
    
    public function setHeaders(core\collection\IHeaderMap $headers) {
        $this->_headers = $headers;
        return $this;
    }

    public function prepareHeaders() {
        if($this->hasCookies()) {
            $this->_cookies->applyTo($this->getHeaders());
        }

        return $this;
    }

    public function hasHeaders() {
        return $this->_headers && !$this->_headers->isEmpty();
    }

    public function getCookies() {
        if(!$this->_cookies) {
            $this->_cookies = new halo\protocol\http\response\CookieCollection();
        }
        
        return $this->_cookies;
    }
    
    public function hasCookies() {
        return $this->_cookies && !$this->_cookies->isEmpty();
    }

    public function onDispatchComplete() {
        if($this->_renderedContent === null) {
            $this->_renderedContent = $this->render();
        }

        $this->getHeaders()->set('Content-Type', $this->getContentType());
        
        return $this;
    }
    
    public function getContent() {
        if($this->_renderedContent === null) {
            $this->_renderedContent = $this->render();
        }
        
        return $this->_renderedContent;
    }
    
    public function getEncodedContent() {
        $content = $this->getContent();
        
        if(!$this->_headers || empty($content)) {
            return $content;
        }
        
        $contentEncoding = $this->_headers->get('content-encoding');
        $transferEncoding = $this->_headers->get('transfer-encoding');
        
        if(!$contentEncoding && !$transferEncoding) {
            return $content;
        }
        
        return halo\protocol\http\response\Base::encodeContent(
            $content, $contentEncoding, $transferEncoding
        );
    }
    
    public function setContentType($type) {
        throw new RuntimeException(
            'View content type cannot be changed'
        );
    }

    public function getContentType() {
        return core\mime\Type::extToMime($this->_type);
    }
    
    public function getContentLength() {
        return strlen($this->getContent());
    }
    
    public function setLastModified(core\time\IDate $date) {
        $this->getHeaders()->set('last-modified', $date);
        return $this;
    }
    
    public function getLastModified() {
        if($this->_headers && $this->_headers->has('last-modified')) {
            return core\time\Date::factory($this->_headers->get('last-modified'));
        }
        
        return new core\time\Date();
    }
    
    public function getHeaderString() {
        $this->prepareHeaders();
        
        return halo\protocol\http\response\Base::buildHeaderString($this->_headers);
    }
    
    public function getResponseString() {
        $output = $this->getHeaderString()."\r\n\r\n";
        $output .= $this->getEncodedContent()."\r\n";
                  
        return $output;
    }
    
    
// Content
    public function getType() {
        return $this->_type;
    }
    
    public function setContentProvider(IContentProvider $provider) {
        $this->_contentProvider = $provider;
        return $this;
    }
    
    public function getContentProvider() {
        return $this->_contentProvider;
    }
    
    
// Args
    public function setArgs(array $args) {
        $this->_checkContentProvider();
        $this->_contentProvider->setArgs($args);
        return $this;
    }
    
    public function addArgs(array $args) {
        $this->_checkContentProvider();
        $this->_contentProvider->addArgs($args);
        return $this;
    }
    
    public function getArgs(array $add=array()) {
        $this->_checkContentProvider();
        return $this->_contentProvider->getArgs($add);
    }
    
    public function setArg($key, $value) {
        $this->_checkContentProvider();
        $this->_contentProvider->setArg($key, $value);
        return $this;
    }
    
    public function getArg($key, $default=null) {
        $this->_checkContentProvider();
        return $this->_contentProvider->getArg($key, $default);
    }
    
    public function removeArg($key) {
        $this->_checkContentProvider();
        $this->_contentProvider->removeArg($key);
        return $this;
    }
    
    public function hasArg($key) {
        $this->_checkContentProvider();
        return $this->_contentProvider->hasArg($key);
    }
    
    public function offsetSet($name, $value) {
        $this->_checkContentProvider();
        $this->_contentProvider->setArg($name, $value);
        return $this;
    }
    
    public function offsetGet($name) {
        $this->_checkContentProvider();
        return $this->_contentProvider->getArg($name);
    }
    
    public function offsetExists($name) {
        $this->_checkContentProvider();
        return $this->_contentProvider->hasArg($name);
    }
    
    public function offsetUnset($name) {
        $this->_checkContentProvider();
        $this->_contentProvider->removeArg($name);
        return $this;
    }
    

// Render
    public function getView() {
        return $this;
    }
    
    public function render() {
        $this->_checkContentProvider();
        
        if($this instanceof IThemedView) {
            $this->getTheme()->renderTo($this);
        }
        
        $this->_beforeRender();
        
        if($this instanceof ILayoutView && $this->shouldUseLayout()) {
            try {
                return aura\view\content\Template::loadLayout(
                    $this, $this->getLayout().'.'.lcfirst($this->getType())
                )->renderTo($this);
            } catch(aura\view\ContentNotFoundException $e) {}
        }

        return $this->_contentProvider->renderTo($this);
    }
    
    protected function _beforeRender() {}
    
    private function _checkContentProvider() {
        if(!$this->_contentProvider) {
            throw new RuntimeException(
                'No content provider has been set for '.$this->_type.' type view',
                404
            );
        }
    }
    
    
// Helpers
    public function __get($member) {
        switch($member) {
            case 'context':
                return $this->getContext();
                
            case 'application':
                return $this->_context->getApplication();
                
            case 'contentProvider':
                return $this->getContentProvider();
                
            default:
                return $this->getHelper($member);
        }
    }

    protected function _loadHelper($name) {
        $class = 'df\\plug\\view\\'.$this->getType().$name;
            
        if(!class_exists($class)) {
            $class = 'df\\plug\\view\\'.$name;
            
            if(!class_exists($class)) {
                return $this->newErrorContainer(
                    new HelperNotFoundException('View helper '.$name.' could not be found')
                );
            }
        }
        
        return new $class($this);
    }
    
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->_context->_($phrase, $data, $plural, $locale);
    }

    public function newErrorContainer(\Exception $e) {
        return new aura\view\content\ErrorContainer($this, $e);
    }
}
