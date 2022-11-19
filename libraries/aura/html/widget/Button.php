<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;

use df\arch;

class Button extends Base implements IButtonWidget, IIconProviderWidget, Dumpable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_BodyContentAware;
    use TWidget_DispositionAware;
    use TWidget_IconProvider;

    public const PRIMARY_TAG = 'button.btn';
    public const ARRAY_INPUT = false;
    public const BUTTON_TYPE = 'button';
    public const HAS_VALUE = true;

    protected $_shouldValidate = true;

    public function __construct(arch\IContext $context, $name, $body = null, $value = null)
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
    public function shouldValidate(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldValidate = $flag;
            return $this;
        }

        return $this->_shouldValidate;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*name' => $this->_name,
            '*value' => $this->_value,
            '*icon' => $this->_icon,
            '%tag' => $this->getTag(),
            '*disposition' => $this->getDisposition()
        ];

        yield 'value' => $this->_body;
    }
}
