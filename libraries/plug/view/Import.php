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

class Import implements aura\view\IHelper {
    
    use aura\view\THelper;
    
    public function template($path, $location=null) {
        try {
            $context = $this->_view->getContext()->spawnInstance($location);
            $template = aura\view\content\Template::loadDirectoryTemplate($context, $path);
            $template->setRenderTarget($this->_view);
            $template->setArgs($this->_view->getArgs());
        
            return $template;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }

    public function themeTemplate($path, $themeId=null) {
        try {
            $template = aura\view\content\Template::loadThemeTemplate($this->_view, $path, $themeId);
            $template->setRenderTarget($this->_view);

            return $template;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }
    
    public function component($path) {
        $args = array_slice(func_get_args(), 1);
        $parts = explode('/', $path);
        $name = array_pop($parts);

        if(empty($parts)) {
            $location = clone $this->_view->getContext()->location;
        } else {
            $location = new arch\Request(implode('/', $parts).'/');
        }

        try {
            $context = $this->_view->getContext()->spawnInstance($location);
            $output = arch\component\Base::factory($context, $name, $args);
            $output->setRenderTarget($this->_view);

            return $output;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }

    public function themeComponent($name) {
        $args = array_slice(func_get_args(), 1);

        if(false !== strpos($name, '/')) {
            $parts = explode('/', $name, 2);
            $themeId = array_shift($parts);
            $name = array_shift($parts);
        } else {
            $themeId = $this->_view->getTheme()->getId();
        }

        try {
            $output = arch\component\Base::themeFactory($this->_view->getContext(), $themeId, $name, $args);
            $output->setRenderTarget($this->_view);

            return $output;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }

    public function menu($id) {
        try {
            return arch\navigation\menu\Base::factory($id);
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }

    public function form($request) {
        $request = arch\Request::factory($request);
        $context = $this->_view->getContext()->spawnInstance($request);
        $action = arch\form\Action::factory($context);

        if(!$action instanceof arch\form\IAction) {
            throw new arch\InvalidArgumentException(
                'Action '.$request.' is not a form action!'
            );
        }

        return $action->dispatchToRenderInline($this->_view);
    }
}
