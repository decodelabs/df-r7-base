<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\theme\facet;

use DecodeLabs\Exceptional;

use DecodeLabs\Genesis;
use df\aura;

abstract class Base implements aura\theme\IFacet
{
    protected $_environments = null;

    public static function factory(string $name, array $config = null): aura\theme\IFacet
    {
        $class = 'df\\aura\\theme\\facet\\' . ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Theme facet ' . $name . ' could not be found'
            );
        }

        return new $class($config ?? []);
    }

    public function __construct(array $config)
    {
        if (isset($config['environments'])) {
            $this->_environments = (array)$config['environments'];
        }
    }

    protected function _checkEnvironment(): bool
    {
        if (empty($this->_environments)) {
            return true;
        }

        return in_array(Genesis::$environment->getName(), $this->_environments);
    }

    # Before render
    public function beforeViewRender(aura\view\IView $view)
    {
        $func = 'before' . $view->getType() . 'ViewRender';

        if (method_exists($this, $func)) {
            $this->$func($view);
        }

        return $this;
    }


    # On content render
    public function onViewContentRender(aura\view\IView $view, $content)
    {
        $func = 'on' . $view->getType() . 'ViewContentRender';

        if (method_exists($this, $func)) {
            if (null !== ($newContent = $this->$func($view, $content))) {
                $content = $newContent;
            }
        }

        return $content;
    }


    # On layout render
    public function onViewLayoutRender(aura\view\IView $view, $content)
    {
        $func = 'on' . $view->getType() . 'ViewLayoutRender';

        if (method_exists($this, $func)) {
            if (null !== ($newContent = $this->$func($view, $content))) {
                $content = $newContent;
            }
        }

        return $content;
    }



    # After render
    public function afterViewRender(aura\view\IView $view, $content)
    {
        $func = 'after' . $view->getType() . 'ViewRender';

        if (method_exists($this, $func)) {
            if (null !== ($newContent = $this->$func($view, $content))) {
                $content = $newContent;
            }
        }

        return $content;
    }
}
