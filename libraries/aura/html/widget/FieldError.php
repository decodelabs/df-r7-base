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

class FieldError extends Base implements IFormOrientedWidget, core\collection\IErrorContainer, core\IDumpable {

    use core\collection\TErrorContainer;

    public function __construct(arch\IContext $context, $errors=null) {
        parent::__construct($context);

        if($errors !== null) {
            if($errors instanceof core\collection\IErrorContainer) {
                $errors = $errors->getErrors();
            }

            $this->addErrors($errors);
        }
    }

    protected function _render() {
        if(empty($this->_errors)) {
            return '';
        }

        $tag = $this->getTag();
        $output = new aura\html\ElementContent();

        foreach($this->_errors as $code => $error) {
            $output->push(
                new aura\html\Element(
                    'div', $error,
                    ['data-errorid' => $code]
                )
            );
        }

        return $tag->renderWith($output, true);
    }


// Dump
    public function getDumpProperties() {
        return [
            'errors' => $this->_errors,
            'tag' => $this->getTag()
        ];
    }
}
