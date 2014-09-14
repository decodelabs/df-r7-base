<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\directory;

use df;
use df\core;
use df\arch;
use df\aura;
use df\halo;

class Directory implements arch\IDirectoryHelper {
    
    use arch\TDirectoryHelper;

// Request
    public function newRequest($request) {
        return arch\Request::factory($request);
    }

    public function normalizeRequest($request, $from=null, $to=null) {
        if(!$request instanceof arch\IRequest) {
            $request = arch\Request::factory($request);
        } else {
            $request = clone $request;
        }
        
        
        if($from !== null) {
            if($from === true) {
                $from = $this->_context->request;
            }
            
            $request->setRedirectFrom($from);
        }
        
        if($to !== null) {
            if($to === true) {
                $to = $this->_context->request;
            }
            
            $request->setRedirectTo($to);
        }

        return $request;
    }

    public function backRequest($default=null, $success=true) {
        $request = $this->_context->request;
        
        if($success && ($redirect = $request->getRedirectTo())) {
            return $redirect;
        } else if((!$success || ($success && !$request->getRedirectTo()))
            && ($redirect = $request->getRedirectFrom())) {
            return $redirect;
        }
            
        if($default === null) {
            $default = $request->getParent();
        } else if(!$default instanceof arch\IRequest) {
            $default = $this->newRequest($default);
        } else {
            $default = clone $default;
        }

        return $default;
    }


// Actions
    public function actionExists($request, $runMode=null) {
        $request = arch\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }

        return null !== arch\Action::getClassFor($request, $runMode);
    }
    
    public function getAction($request, $runMode=null) {
        $request = arch\Request::factory($request);
        $context = arch\Context::factory($request, $runMode);
        return arch\Action::factory($context);
    }
    
    public function controllerExists($request, $runMode=null) {
        $request = arch\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }

        return null !== arch\Controller::getClassFor($request, $runMode);
    }

    public function getController($request) {
        return arch\Controller::factory(
            arch\Context::factory($request)
        );
    }
    
    public function getComponent($path) {
        $parts = explode('/', $path);
        $name = array_pop($parts);

        if(empty($parts)) {
            $location = clone $this->_context->location;
        } else {
            $location = new Arch\Request(implode('/', $parts).'/');
        }

        $context = arch\Context::factory($location);
        $args = array_slice(func_get_args(), 1);
        return arch\component\Base::factory($context, $name, $args);
    }

    public function getThemeComponent($path) {
        $parts = explode('/', $path);
        $name = array_pop($parts);

        if(empty($parts)) {
            $themeId = aura\theme\Config::getInstance()->getThemeIdFor($this->_context->location->getArea());
        } else {
            $themeId = array_shift($parts);
        }

        $context = $this->_context->spawnInstance();
        $args = array_slice(func_get_args(), 1);
        return arch\component\Base::themeFactory($context, $themeId, $name, $args);
    }

    public function getScaffold($context=true) {
        if($context === true) {
            $context = $this->_context;
        }
        
        if($context instanceof arch\IContext) {
            $request = clone $context->location;
        } else {
            $request = arch\Request::factory($context);
        }

        $context = arch\Context::factory($request);
        return arch\scaffold\Base::factory($context);
    }
}
