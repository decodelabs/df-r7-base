<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\arch;
use df\aura;

class Apex implements arch\IDirectoryHelper {
    
    use arch\TDirectoryHelper;
    use aura\view\TViewAwareDirectoryHelper;

    public function future($type) {
        $args = array_slice(func_get_args(), 1);
        
        return function(core\IHelperProvider $target) use($type, $args) {
            return call_user_func_array([$target->apex, $type], $args);
        };
    }

// Aura
    public function view($path, array $slots=null) {
        $parts = explode('.', $path);
        $location = $this->context->extractDirectoryLocation($path);
        $view = $this->newView(array_pop($parts), $location);
        
        $view->setContentProvider(
            aura\view\content\Template::loadDirectoryTemplate($view->getContext(), $path)
        );

        if($slots) {
            $view->addSlots($slots);
        }
        
        return $view;
    }
    
    public function newView($type, $request=null) {
        return aura\view\Base::factory($type, $this->context->spawnInstance($request));
    }

    public function newWidgetView($request=null) {
        $view = $this->newView($this->context->location->getType(), $request);
        $view->setContentProvider(new aura\view\content\WidgetContentProvider($view->getContext()));
        return $view;
    }
    
    public function template($path, array $slots=null) {
        $location = $this->context->extractDirectoryLocation($path);
        $template = aura\view\content\Template::loadDirectoryTemplate(
            $this->context->spawnInstance($location), $path
        );

        if($this->view) {
            $template->setRenderTarget($this->view);
        }

        if($slots) {
            $template->addSlots($slots);
        }

        return $template;
    }

    public function themeTemplate($path, array $slots=null) {
        $themeId = $this->context->extractThemeId($path);

        if(!$themeId && $this->view) {
            $themeId = $this->view->getTheme()->getId();
        }

        $template = aura\view\content\Template::loadThemeTemplate(
            $this->context, $path, $themeId
        );

        if($this->view) {
            $template->setRenderTarget($this->view);
        }

        if($slots) {
            $template->addSlots($slots);
        }

        return $template;
    }

    public function getTheme($id=null) {
        if($id === null) {
            $id = $this->context;
        }

        return aura\theme\Base::factory($id);
    }


// Actions
    public function actionExists($request, $runMode=null) {
        $request = arch\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->context->getRunMode();
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
            $runMode = $this->context->getRunMode();
        }

        return null !== arch\Controller::getClassFor($request, $runMode);
    }

    public function getController($request) {
        return arch\Controller::factory(
            arch\Context::factory($request)
        );
    }
    
    public function component($path) {
        $output = arch\component\Base::factory(
            $this->context->spawnInstance(
                $this->context->extractDirectoryLocation($path)
            ), 
            $path, 
            array_slice(func_get_args(), 1)
        );

        if($this->view) {
            $output->setRenderTarget($this->view);
        }

        return $output;
    }

    public function themeComponent($path) {
        $themeId = $this->context->extractThemeId($path, true);

        if(!$themeId && $this->view) {
            $themeId = $this->view->getTheme()->getId();
        }

        $output = arch\component\Base::themeFactory(
            $this->context->spawnInstance(), 
            $themeId, 
            $path, 
            array_slice(func_get_args(), 1)
        );

        if($this->view) {
            $output->setRenderTarget($this->view);
        }

        return $output;
    }

    public function scaffold($context=true) {
        if($context === true) {
            $context = $this->context;
        }
        
        if($context instanceof arch\IContext) {
            $request = clone $context->location;
        } else {
            $request = arch\Request::factory($context);
        }

        $context = arch\Context::factory($request);
        return arch\scaffold\Base::factory($context);
    }

    public function form($request) {
        if(!$this->view) {
            throw new arch\RuntimeException(
                'Cannot prepare form for rendering, context has no view'
            );
        }

        $request = arch\Request::factory($request);
        $context = $this->context->spawnInstance($request);
        $action = arch\form\Action::factory($context);

        if(!$action instanceof arch\form\IAction) {
            throw new arch\InvalidArgumentException(
                'Action '.$request.' is not a form action!'
            );
        }

        return $action->dispatchToRenderInline($this->view);
    }

// Navigation
    public function menu($id) {
        return arch\navigation\menu\Base::factory($this->context, $id);
    }

    public function breadcrumbs($empty=false) {
        if(!$output = $this->context->application->getRegistryObject('breadcrumbs')) {
            if($empty) {
                $output = new arch\navigation\breadcrumbs\EntryList();
            } else {
                $output = arch\navigation\breadcrumbs\EntryList::generateFromRequest(
                    $this->context->request
                );
            }
            
            $this->context->application->setRegistryObject($output);
        }

        return $output;
    }

    public function getLocationTitle() {
        $breadcrumbs = $this->getBreadcrumbs();

        if($entry = $breadcrumbs->getLastEntry()) {
            return $entry->getBody();
        }

        return $this->context->application->getName();
    }

    public function clearMenuCache($id=null) {
        if($id !== null) {
            arch\navigation\menu\Base::clearCacheFor($this->context, $id);
        } else {
            arch\navigation\menu\Base::clearCache($this->context);
        }

        return $this;
    }
}