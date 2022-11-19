<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;
use df\aura;

use df\core;

class FieldError extends Base implements
    IFormOrientedWidget,
    core\collection\IErrorContainer,
    Dumpable
{
    use core\collection\TErrorContainer;

    public const PRIMARY_TAG = 'div.list.errors';

    public function __construct(arch\IContext $context, $errors = null)
    {
        parent::__construct($context);

        if ($errors !== null) {
            if ($errors instanceof core\collection\IErrorContainer) {
                $errors = $errors->getErrors();
            }

            $this->addErrors($errors);
        }
    }

    protected function _render()
    {
        if (empty($this->_errors)) {
            return '';
        }

        $tag = $this->getTag();
        $output = new aura\html\ElementContent();

        foreach ($this->_errors as $code => $error) {
            $output->push(
                new aura\html\Element(
                    'div',
                    $error,
                    ['data-errorid' => $code]
                )
            );
        }

        return $tag->renderWith($output, true);
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:%tag' => $this->getTag();
        yield 'values' => $this->_errors;
    }
}
