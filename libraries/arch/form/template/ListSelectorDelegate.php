<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\template;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

abstract class ListSelectorDelegate extends arch\form\Delegate implements 
    arch\form\IInlineFieldRenderableDelegate, 
    arch\form\ISelectorDelegate,
    arch\form\IDependentDelegate {

    use arch\form\TForm_InlineFieldRenderableDelegate;
    use arch\form\TForm_SelectorDelegate;
    use arch\form\TForm_SelectorDelegateQueryTools;
    use arch\form\TForm_ValueListSelectorDelegate;
    use arch\form\TForm_DependentDelegate;

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fa) {
        $options = $this->_getOptionsList();
        $type = $this->_isForMany ? 'multiSelectList' : 'selectList';
        $select = $this->html->{$type}(
                $this->fieldName('selected'),
                $this->values->selected,
                $options
            )
            ->isRequired($this->_isRequired);

        $fa->push(
            $this->html('div.widget-selection', [
                $this->html('div.body', $select),

                $this->html->buttonArea(
                    $this->html->eventButton(
                            $this->eventName('clear'), 
                            $this->_('Clear')
                        )
                        ->shouldValidate(false)
                        ->setIcon('remove')
                )
            ])
        );
    }

    abstract protected function _getOptionsList();
}