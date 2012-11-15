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
    
    public function template($path, $contextRequest=null) {
        try {
            $context = $this->_view->getContext()->spawnInstance($contextRequest);
            $template = aura\view\content\Template::loadDirectoryTemplate($context, $path);
            $template->setRenderTarget($this->_view);
        
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
    
    public function component($name, $contextRequest=null) {
        try {
            $context = $this->_view->getContext()->spawnInstance($contextRequest);
            return arch\Component::factory($context, $name);
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
}
