<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\arch as archLib;
use df\aura as auraLib;

class Aura implements archLib\IContextHelper {
    
    use archLib\TContextHelper;
    
    public function getView($path, $request=null) {
        $parts = explode('.', $path);
        $view = $this->getBarebonesView(array_pop($parts), $request);
        
        $view->setContentProvider(
            auraLib\view\content\Template::loadDirectoryTemplate($view->getContext(), $path)
        );
        
        return $view;
    }
    
    public function getBarebonesView($type, $request=null) {
        return auraLib\view\Base::factory($type, $this->_context->spawnInstance($request));
    }
    
    public function getDirectoryTemplate($path, $request=null) {
        return auraLib\view\content\Template::loadDirectoryTemplate($this->_context->spawnInstance($request), $path);
    }

    public function getThemeTemplate(auraLib\view\IView $view, $path, $themeId=null) {
        return auraLib\view\content\Template::loadThemeTemplate($view, $path, $themeId);
    }

    public function getWidgetContainer($request=null) {
        $view = $this->getBarebonesView($this->_context->getRequest()->getType(), $request);
        $view->setContentProvider($output = new auraLib\view\content\WidgetContentProvider($view->getContext()));
        $output->setRenderTarget($view);

        return $output;
    }
}