<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Button extends Base implements IButtonWidget, IIconProviderWidget, Inspectable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_BodyContentAware;
    use TWidget_DispositionAware;
    use TWidget_IconProvider;

    const PRIMARY_TAG = 'button.btn';
    const ARRAY_INPUT = false;
    const BUTTON_TYPE = 'button';
    const HAS_VALUE = true;

    protected $_shouldValidate = true;

    public function __construct(arch\IContext $context, $name, $body=null, $value=null)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
        $this->setBody($body);
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $tag->setAttribute('type', static::BUTTON_TYPE);

        $this->_applyFormDataAttributes($tag, static::HAS_VALUE);
        $this->_applyInputAttributes($tag);

        if (!$this->_shouldValidate) {
            $tag->setAttribute('formnovalidate', true);
        }

        if ($this->_disposition !== null) {
            $tag->addClass($this->getDisposition());
        }

        $icon = $this->_generateIcon();

        return $tag->renderWith(
            [$icon, $this->_body]
        );
    }


    // Validate
    public function shouldValidate(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_shouldValidate = $flag;
            return $this;
        }

        return $this->_shouldValidate;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*name' => $inspector($this->_name),
                '*value' => $inspector($this->_value),
                '*icon' => $inspector($this->_icon),
                '%tag' => $inspector($this->getTag()),
                '*disposition' => $inspector($this->getDisposition())
            ])
            ->setValues([$inspector($this->_body)])
            ->setShowKeys(false);
    }
}
