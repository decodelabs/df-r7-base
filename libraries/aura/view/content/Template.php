<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\view\content;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\arch;
use df\aura;
use df\core;

class Template implements aura\view\ITemplate, Dumpable
{
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

    public static function loadDirectoryTemplate(arch\IContext $context, $path)
    {
        $request = $context->location;
        $contextPath = trim((string)$request->getDirectoryLocation(), '/');
        $contextParts = explode('/', $contextPath);
        $contextParts[0] = 'shared';
        $sharedContextPath = implode('/', $contextParts);
        $themeId = ucfirst($context->apex->getTheme()->getId());
        $lookupPaths = [];

        $parts = explode('/', trim((string)$path, '/'));
        $fileName = array_pop($parts);
        $base = rtrim('apex/directory/' . $contextPath . '/_templates/' . implode('/', $parts), '/');
        $sharedBase = rtrim('apex/directory/' . $sharedContextPath . '/_templates/' . implode('/', $parts), '/');

        $fileParts = explode('.', $fileName);
        $topName = array_shift($fileParts);
        $themeName = $topName . '@' . $themeId . '.' . implode('.', $fileParts) . '.php';
        $rootName = $topName . '.' . implode('.', $fileParts) . '.php';

        $lookupPaths[] = $base . '/' . $themeName;
        $lookupPaths[] = $base . '/' . $rootName;

        if (!$request->isArea('shared')) {
            $lookupPaths[] = $sharedBase . '/' . $rootName;
        }

        $absolutePath = null;

        foreach ($lookupPaths as $i => $path) {
            if ($absolutePath = $context->findFile($path)) {
                break;
            }
        }

        if (!$absolutePath) {
            if (false !== strpos($path, '/')) {
                $path = '#/' . $path;
            }

            throw Exceptional::{'df/aura/view/NotFound'}(
                'Template ~' . rtrim((string)$request->getDirectoryLocation(), '/') . '/' . $path . ' could not be found'
            );
        }

        return new self($context, $absolutePath);
    }

    public static function loadThemeTemplate(arch\IContext $context, $path, $themeId = null)
    {
        if ($themeId === null) {
            $themeId = $context->apex->getTheme()->getId();
        }

        $lookupPaths = [];
        $area = $context->location->getArea();
        $parts = explode('.', $path);
        $type = array_pop($parts);
        $pathName = implode('.', $parts);


        $lookupPaths[] = 'apex/themes/' . $themeId . '/templates/' . $pathName . '#' . $area . '.' . $type . '.php';
        $lookupPaths[] = 'apex/themes/' . $themeId . '/templates/' . $pathName . '.' . $type . '.php';

        if ($themeId !== 'shared') {
            $lookupPaths[] = 'apex/themes/shared/templates/' . $pathName . '#' . $area . '.' . $type . '.php';
            $lookupPaths[] = 'apex/themes/shared/templates/' . $pathName . '.' . $type . '.php';
        }

        foreach ($lookupPaths as $testPath) {
            if ($templatePath = $context->findFile($testPath)) {
                break;
            }
        }

        if (!$templatePath) {
            throw Exceptional::{'df/aura/view/NotFound'}(
                'Theme template ' . $path . ' could not be found'
            );
        }

        return new self($context, $templatePath);
    }

    public static function loadLayout(aura\view\ILayoutView $view, $innerContent = null, $pathName = null, $type = null)
    {
        if ($pathName === null) {
            $pathName = $view->getLayout();
        }

        if ($type === null) {
            $type = lcfirst($view->getType());
        }

        $theme = $view->getTheme();
        $context = $view->getContext();

        $lookupPaths = [];
        $area = $context->location->getArea();
        $themeId = $theme->getId();

        $lookupPaths[] = 'apex/themes/' . $themeId . '/layouts/' . $pathName . '#' . $area . '.' . $type . '.php';
        $lookupPaths[] = 'apex/themes/' . $themeId . '/layouts/' . $pathName . '.' . $type . '.php';

        if ($themeId !== 'shared') {
            $lookupPaths[] = 'apex/themes/shared/layouts/' . $pathName . '#' . $area . '.' . $type . '.php';
            $lookupPaths[] = 'apex/themes/shared/layouts/' . $pathName . '.' . $type . '.php';
        }

        foreach ($lookupPaths as $testPath) {
            if ($layoutPath = $context->findFile($testPath)) {
                break;
            }
        }

        if (!$layoutPath) {
            throw Exceptional::{'df/aura/view/NotFound'}(
                'Layout ' . $pathName . '.' . $type . ' could not be found'
            );
        }

        $output = new self($context, $layoutPath, true);
        $output->_innerContent = $innerContent;

        return $output;
    }

    public function __construct(arch\IContext $context, $absolutePath, $isLayout = false)
    {
        if (!is_file($absolutePath)) {
            throw Exceptional::{'df/aura/view/NotFound'}(
                'Template ' . $absolutePath . ' could not be found'
            );
        }

        $this->_path = $absolutePath;
        $this->context = $context;
        $this->_isLayout = $isLayout;
    }


    // Renderable
    public function getView()
    {
        if (!$this->view) {
            throw Exceptional::{'df/aura/view/NoView,NoContext'}(
                'This template is not currently rendering'
            );
        }

        return $this->view;
    }

    public function render()
    {
        if ($this->_isRendering) {
            throw Exceptional::Logic(
                'Rendering is already in progress'
            );
        }

        $____target = $this->getRenderTarget();
        $this->_isRendering = true;
        $this->view = $____target->getView();


        if ($this->_isLayout && $this->_innerContent === null) {
            // Prepare inner template content before rendering to ensure
            // sub templates can affect layout properties
            $this->renderInnerContent();
        }

        try {
            $slots = $this->getSlots();
            extract($slots, \EXTR_OVERWRITE | \EXTR_PREFIX_SAME, 'slot');

            ob_start();
            require $this->_path;
            $output = ob_get_clean();

            $this->_isRendering = false;
        } catch (\Throwable $e) {
            if (ob_get_level()) {
                ob_end_clean();
            }

            $this->_isRendering = false;
            throw $e;
        }

        return $output;
    }

    public function toResponse()
    {
        return $this->view;
    }

    protected function renderInnerContent()
    {
        if (!$this->_isLayout || $this->_innerContent === false) {
            return null;
        }

        if ($this->_innerContent !== null) {
            return $this->_innerContent;
        }

        $this->_innerContent = false;
        $provider = $this->getView()->getContentProvider();

        if ($provider && $provider !== $this) {
            return $this->_innerContent = $provider->renderTo($this);
        }
    }

    public function isRendering()
    {
        return $this->_isRendering;
    }

    public function isLayout()
    {
        return $this->_isLayout;
    }

    public function toString(): string
    {
        if (!$this->_renderTarget) {
            throw Exceptional::{'df/aura/view/NoView,NoContext'}(
                'No render target has been set'
            );
        }

        return (string)$this->renderTo($this->_renderTarget);
    }


    // Slots
    public function getSlots()
    {
        $output = [];

        if ($this->view) {
            $output = $this->view->getSlots();
        }

        return array_merge($output, $this->slots);
    }

    public function clearSlots()
    {
        if ($this->view) {
            $this->view->clearSlots();
        }

        $this->slots = [];
        return $this;
    }

    public function setSlot(string $key, $value)
    {
        if ($this->view) {
            $this->view->setSlot($key, $value);
        } else {
            $this->slots[$key] = $value;
        }

        return $this;
    }

    public function hasSlot(string ...$keys): bool
    {
        if ($this->view && $this->view->hasSlot(...$keys)) {
            return true;
        }

        foreach ($keys as $key) {
            if (isset($this->slots[$key])) {
                return true;
            }
        }

        return false;
    }

    public function slotExists(string $key)
    {
        if ($this->view && $this->view->slotExists($key)) {
            return true;
        }

        if (!empty($this->slots)) {
            return array_key_exists($key, $this->slots);
        }

        return false;
    }

    public function getSlot(string $key, $default = null)
    {
        if (isset($this->slots[$key])) {
            return $this->slots[$key];
        }

        if ($this->view) {
            return $this->view->getSlot($key, $default);
        }

        return $default;
    }

    public function removeSlot(string $key)
    {
        unset($this->slots[$key]);

        if ($this->view) {
            $this->view->removeSlot($key);
        }

        return $this;
    }


    public function startSlotCapture($key)
    {
        $this->_checkView();
        $this->view->startSlotCapture($key);
        return $this;
    }

    public function endSlotCapture()
    {
        $this->_checkView();
        $this->view->endSlotCapture();
        return $this;
    }

    public function isCapturingSlot()
    {
        if ($this->view) {
            return $this->view->isCapturingSlot();
        } else {
            return false;
        }
    }

    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->setSlot((string)$key, $value);
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->getSlot($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->hasSlot($key);
    }

    public function offsetUnset(mixed $key): void
    {
        $this->removeSlot((string)$key);
    }


    // Helpers
    public function translate(array $args): string
    {
        return $this->context->i18n->translate($args);
    }

    protected function _checkView()
    {
        if (!$this->view) {
            throw Exceptional::{'df/aura/view/NoView,NoContext'}(
                'No view available for content provider to interact with'
            );
        }
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*path' => $this->_path,
            'context' => $this->context,
            'view' => $this->view
        ];

        yield 'values' => $this->slots;
    }
}
