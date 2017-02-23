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
use df\flex;

class Apex implements arch\IDirectoryHelper, aura\view\IContextSensitiveHelper {

    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;

    public function future($type, ...$args) {
        return function(core\IHelperProvider $target) use($type, $args) {
            return $target->apex->{$type}(...$args);
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

    public function newWidgetView($callback=null) {
        $view = $this->newView($this->context->location->getType());
        $view->setContentProvider($content = new aura\view\content\WidgetContentProvider($view->getContext()));

        if($callback) {
            $content->push(core\lang\Callback::call($callback, $view));
        }

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

        if(!$themeId) {
            $themeId = $this->getTheme()->getId();
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
            if($this->view) {
                return $this->view->getTheme();
            }

            $id = $this->context;
        }

        return aura\theme\Base::factory($id);
    }

// Nodes
    public function nodeExists($request, $runMode=null) {
        $request = $this->context->uri->directoryRequest($request);

        if($runMode === null) {
            $runMode = $this->context->getRunMode();
        }

        if(null !== arch\node\Base::getClassFor($request, $runMode)) {
            return true;
        }

        $context = $this->context->spawnInstance($request, true);

        try {
            $scaffold = $this->scaffold($context);
            $scaffold->loadNode();
            return true;
        } catch(arch\scaffold\IError $e) {}

        return arch\Transformer::isNodeDeliverable($context);
    }

    public function getNode($request, $runMode=null) {
        $request = $this->context->uri->directoryRequest($request);
        $context = arch\Context::factory($request, $runMode);
        return arch\node\Base::factory($context);
    }

    public function findNodesIn($request, $type=null) {
        $request = $this->context->uri->directoryRequest($request);
        $path = $request->getLibraryPath().'/_nodes';

        foreach(df\Launchpad::$loader->lookupClassList($path) as $name => $class) {
            if($type !== null && 0 !== stripos($name, $type)) {
                continue;
            }

            $requestParts = array_slice(explode('\\', $class), 3, -2);
            $requestParts[] = substr($name, 4);

            array_walk($requestParts, function(&$value) {
                $value = flex\Text::formatNodeSlug($value);
            });

            yield arch\Request::factory('~'.implode('/', $requestParts));
        }
    }

    public function controllerExists($request, $runMode=null) {
        $request = $this->context->uri->directoryRequest($request);

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

    public function component($path, ...$args) {
        $output = arch\component\Base::factory(
            $this->context->spawnInstance(
                $this->context->extractDirectoryLocation($path)
            ),
            $path,
            $args
        );

        if($this->view) {
            $output->setRenderTarget($this->view);
        }

        return $output;
    }

    public function themeComponent($path, ...$args) {
        $themeId = $this->context->extractThemeId($path, true);

        if(!$themeId && $this->view) {
            $themeId = $this->view->getTheme()->getId();
        }

        $output = arch\component\Base::themeFactory(
            $this->context->spawnInstance(),
            $themeId,
            $path,
            $args
        );

        if($this->view) {
            $output->setRenderTarget($this->view);
        }

        return $output;
    }

    public function scaffold($context=true) {
        if(!$context instanceof arch\IContext) {
            if($context === true) {
                $request = clone $this->context->location;
            } else {
                $request = $this->context->uri->directoryRequest($context);
            }

            $context = arch\Context::factory($request);
        }

        return arch\scaffold\Base::factory($context);
    }

    public function form($request) {
        if(!$this->view) {
            throw core\Error::{'aura/view/ENoView,ENoContext'}(
                'Cannot prepare form for rendering, context has no view'
            );
        }

        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request);
        $node = arch\node\Form::factory($context);

        if(!$node instanceof arch\node\IFormNode) {
            throw core\Error::{'arch/ENotForm,EArgument'}(
                'Node '.$request.' is not a form!'
            );
        }

        return $node->dispatchToRenderInline($this->view);
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
        $breadcrumbs = $this->breadcrumbs();

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