<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\content;

use df;
use df\core;
use df\aura;
use df\arch;

class Template implements aura\view\ITemplate, core\IDumpable {

    use core\TContextAware;
    use core\TStringProvider;
    use aura\view\TView_DeferredRenderable;
    use aura\view\TView_CascadingHelperProvider;
    use aura\view\TView_SlotContainer;

    public $slots = [];

    private $_path;
    private $_isRendering = false;
    private $_isLayout = false;
    private $_innerContent = null;

    public static function loadDirectoryTemplate(arch\IContext $context, $path) {
        $request = $context->location;
        $contextPath = $request->getDirectoryLocation();
        $lookupPath = 'apex/directory/'.$contextPath.'/_templates/'.ltrim($path, '/').'.php';

        if(!$absolutePath = $context->findFile($lookupPath)) {
            if(!$request->isArea('shared')) {
                $parts = explode('/', $contextPath);
                array_shift($parts);
                array_unshift($parts, 'shared');
                $contextPath = implode('/', $parts);
                $lookupPath = 'apex/directory/'.$contextPath.'/_templates/'.ltrim($path, '/').'.php';
                $absolutePath = $context->findFile($lookupPath);
            }
        }

        if(!$absolutePath) {
            if(false !== strpos($path, '/')) {
                $path = '#/'.$path;
            }

            throw core\Error::{'aura/view/ENotFound'}(
                'Template ~'.rtrim($request->getDirectoryLocation(), '/').'/'.$path.' could not be found'
            );
        }

        return new self($context, $absolutePath);
    }

    public static function loadThemeTemplate(arch\IContext $context, $path, $themeId=null) {
        if($themeId === null) {
            $themeId = $context->apex->getTheme()->getId();
        }

        $lookupPaths = [];
        $area = $context->location->getArea();
        $parts = explode('.', $path);
        $type = array_pop($parts);
        $pathName = implode('.', $parts);


        $lookupPaths[] = 'apex/themes/'.$themeId.'/templates/'.$pathName.'#'.$area.'.'.$type.'.php';
        $lookupPaths[] = 'apex/themes/'.$themeId.'/templates/'.$pathName.'.'.$type.'.php';

        if($themeId !== 'shared') {
            $lookupPaths[] = 'apex/themes/shared/templates/'.$pathName.'#'.$area.'.'.$type.'.php';
            $lookupPaths[] = 'apex/themes/shared/templates/'.$pathName.'.'.$type.'.php';
        }

        foreach($lookupPaths as $testPath) {
            if($templatePath = $context->findFile($testPath)) {
                break;
            }
        }

        if(!$templatePath) {
            throw core\Error::{'aura/view/ENotFound'}(
                'Theme template '.$path.' could not be found'
            );
        }

        return new self($context, $templatePath);
    }

    public static function loadLayout(aura\view\ILayoutView $view, $innerContent=null, $pathName=null, $type=null) {
        if($pathName === null) {
            $pathName = $view->getLayout();
        }

        if($type === null) {
            $type = lcfirst($view->getType());
        }

        $theme = $view->getTheme();
        $context = $view->getContext();

        $lookupPaths = [];
        $area = $context->location->getArea();
        $themeId = $theme->getId();

        $lookupPaths[] = 'apex/themes/'.$themeId.'/layouts/'.$pathName.'#'.$area.'.'.$type.'.php';
        $lookupPaths[] = 'apex/themes/'.$themeId.'/layouts/'.$pathName.'.'.$type.'.php';

        if($themeId !== 'shared') {
            $lookupPaths[] = 'apex/themes/shared/layouts/'.$pathName.'#'.$area.'.'.$type.'.php';
            $lookupPaths[] = 'apex/themes/shared/layouts/'.$pathName.'.'.$type.'.php';
        }

        foreach($lookupPaths as $testPath) {
            if($layoutPath = $context->findFile($testPath)) {
                break;
            }
        }

        if(!$layoutPath) {
            throw core\Error::{'aura/view/ENotFound'}(
                'Layout '.$pathName.'.'.$type.' could not be found'
            );
        }

        $output = new self($context, $layoutPath, true);
        $output->_innerContent = $innerContent;

        return $output;
    }

    public function __construct(arch\IContext $context, $absolutePath, $isLayout=false) {
        if(!is_file($absolutePath)) {
            throw core\Error::{'aura/view/ENotFound'}(
                'Template '.$absolutePath.' could not be found'
            );
        }

        $this->_path = $absolutePath;
        $this->context = $context;
        $this->_isLayout = $isLayout;
    }


// Renderable
    public function getView() {
        if(!$this->view) {
            throw core\Error::{'aura/view/ENoView,ENoContext'}(
                'This template is not currently rendering'
            );
        }

        return $this->view;
    }

    public function render() {
        if($this->_isRendering) {
            throw core\Error::ELogic('Rendering is already in progress');
        }

        $____target = $this->getRenderTarget();
        $this->_isRendering = true;
        $this->view = $____target->getView();


        if($this->_isLayout && $this->_innerContent === null) {
            // Prepare inner template content before rendering to ensure
            // sub templates can affect layout properties
            $this->renderInnerContent();
        }

        try {
            extract($this->getSlots(), \EXTR_OVERWRITE | \EXTR_PREFIX_SAME, 'slot');

            ob_start();
            require $this->_path;
            $output = ob_get_clean();

            $this->_isRendering = false;
            $this->view = null;
        } catch(\Throwable $e) {
            if(ob_get_level()) {
                ob_end_clean();
            }

            $this->_isRendering = false;
            $this->view = null;

            throw $e;
        }

        return $output;
    }

    public function toResponse() {
        return $this->view;
    }

    protected function renderInnerContent() {
        if(!$this->_isLayout || $this->_innerContent === false) {
            return null;
        }

        if($this->_innerContent !== null) {
            return $this->_innerContent;
        }

        $this->_innerContent = false;
        $provider = $this->getView()->getContentProvider();

        if($provider && $provider !== $this) {
            return $this->_innerContent = $provider->renderTo($this);
        }
    }

    public function isRendering() {
        return $this->_isRendering;
    }

    public function isLayout() {
        return $this->_isLayout;
    }

    public function toString(): string {
        if(!$this->_renderTarget) {
            throw core\Error::{'aura/view/ENoView,ENoContext'}(
                'No render target has been set'
            );
        }

        return (string)$this->renderTo($this->_renderTarget);
    }


// Slots
    public function getSlots() {
        $output = [];

        if($this->view) {
            $output = $this->view->getSlots();
        }

        return array_merge($output, $this->slots);
    }

    public function clearSlots() {
        if($this->view) {
            $this->view->clearSlots();
        }

        $this->slots = [];
        return $this;
    }

    public function setSlot(string $key, $value) {
        if($this->view) {
            $this->view->setSlot($key, $value);
        } else {
            $this->slots[$key] = $value;
        }

        return $this;
    }

    public function hasSlot(string ...$keys): bool {
        if($this->view && $this->view->hasSlot(...$keys)) {
            return true;
        }

        foreach($keys as $key) {
            if(isset($this->slots[$key])) {
                return true;
            }
        }

        return false;
    }

    public function slotExists(string $key) {
        if($this->view && $this->view->slotExists($key)) {
            return true;
        }

        if(!empty($this->slots)) {
            return array_key_exists($key, $this->slots);
        }

        return false;
    }

    public function getSlot(string $key, $default=null) {
        if(isset($this->slots[$key])) {
            return $this->slots[$key];
        }

        if($this->view) {
            return $this->view->getSlot($key, $default);
        }

        return $default;
    }

    public function removeSlot(string $key) {
        unset($this->slots[$key]);

        if($this->view) {
            $this->view->removeSlot($key);
        }

        return $this;
    }


    public function startSlotCapture($key) {
        $this->_checkView();
        $this->view->startSlotCapture($key);
        return $this;
    }

    public function endSlotCapture() {
        $this->_checkView();
        $this->view->endSlotCapture();
        return $this;
    }

    public function isCapturingSlot() {
        if($this->view) {
            return $this->view->isCapturingSlot();
        } else {
            return false;
        }
    }

    public function offsetSet($key, $value) {
        return $this->setSlot($key, $value);
    }

    public function offsetGet($key) {
        return $this->getSlot($key);
    }

    public function offsetExists($key) {
        return $this->hasSlot($key);
    }

    public function offsetUnset($key) {
        return $this->removeSlot($key);
    }


// Escaping
    public function esc($value, $default=null) {
        $this->_checkView();

        if($value === null) {
            $value = $default;
        }

        return $this->view->esc($value);
    }


// Helpers
    public function translate(array $args): string {
        return $this->context->i18n->translate($args);
    }

    protected function _checkView() {
        if(!$this->view) {
            throw core\Error::{'aura/view/ENoView,ENoContext'}(
                'No view available for content provider to interact with'
            );
        }
    }


// Dump
    public function getDumpProperties() {
        return [
            'path' => $this->_path,
            'slots' => $this->slots,
            'context' => $this->context,
            'view' => $this->view
        ];
    }
}
