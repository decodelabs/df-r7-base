<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\arch;
use df\aura as auraLib;

class Aura implements arch\IDirectoryHelper {
    
    use arch\TDirectoryHelper;
    
    public function getView($path, $request=null) {
        $parts = explode('.', $path);
        $location = $this->context->extractDirectoryLocation($path);
        $view = $this->getBarebonesView(array_pop($parts), $location);
        
        $view->setContentProvider(
            auraLib\view\content\Template::loadDirectoryTemplate($view->getContext(), $path)
        );
        
        return $view;
    }
    
    public function getBarebonesView($type, $request=null) {
        return auraLib\view\Base::factory($type, $this->context->spawnInstance($request));
    }
    
    public function getDirectoryTemplate($path, $request=null) {
        return auraLib\view\content\Template::loadDirectoryTemplate($this->context->spawnInstance($request), $path);
    }

    public function getThemeTemplate(auraLib\view\IView $view, $path) {
        $themeId = $this->context->extractThemeId($path);
        return auraLib\view\content\Template::loadThemeTemplate($view, $path, $themeId);
    }

    public function getWidgetContainer($request=null) {
        $view = $this->getBarebonesView($this->context->location->getType(), $request);
        $view->setContentProvider($output = new auraLib\view\content\WidgetContentProvider($view->getContext()));
        $output->setRenderTarget($view);

        return $output;
    }
}