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

class Import implements auraLib\view\IContextSensitiveHelper {
    
    use auraLib\view\THelper;
    
    public function template($path) {
        try {
            $location = $this->_context->extractDirectoryLocation($path);
            $context = $this->_context->spawnInstance($location);
            $template = auraLib\view\content\Template::loadDirectoryTemplate($context, $path);
            $template->setRenderTarget($this->_view);
            $template->setArgs($this->_view->getArgs());
        
            return $template;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }

    public function themeTemplate($path) {
        try {
            $themeId = $this->_context->extractThemeId($path);
            $template = auraLib\view\content\Template::loadThemeTemplate($this->_view, $path, $themeId);
            $template->setRenderTarget($this->_view);

            return $template;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }
    
    public function component($path) {
        $args = array_slice(func_get_args(), 1);

        try {
            $location = $this->_context->extractDirectoryLocation($path);
            $context = $this->_context->spawnInstance($location);
            $output = arch\component\Base::factory($context, $path, $args);
            $output->setRenderTarget($this->_view);

            return $output;
        } catch(\Exception $e) {
            return $this->_view->newErrorContainer($e);
        }
    }

    public function themeComponent($name) {
        $args = array_slice(func_get_args(), 1);
        $themeId = $this->_context->extractThemeId($name);

        if($themeId === null) {
            $themeId = $this->_view->getTheme()->getId();
        }

        try {
            $output = arch\component\Base::themeFactory($this->_context, $themeId, $name, $args);
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
        $context = $this->_context->spawnInstance($request);
        $action = arch\form\Action::factory($context);

        if(!$action instanceof arch\form\IAction) {
            throw new arch\InvalidArgumentException(
                'Action '.$request.' is not a form action!'
            );
        }

        return $action->dispatchToRenderInline($this->_view);
    }
}
