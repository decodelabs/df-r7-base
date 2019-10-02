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

class FieldError extends Base implements IFormOrientedWidget, core\collection\IErrorContainer, Inspectable
{
    use core\collection\TErrorContainer;

    const PRIMARY_TAG = 'div.list.errors';

    public function __construct(arch\IContext $context, $errors=null)
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
                    'div', $error,
                    ['data-errorid' => $code]
                )
            );
        }

        return $tag->renderWith($output, true);
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '%tag' => $inspector($this->getTag())
            ])
            ->setValues($inspector->inspectList($this->_errors));
    }
}
