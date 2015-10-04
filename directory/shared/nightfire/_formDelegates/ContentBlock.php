<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\shared\nightfire\_formDelegates;

use df;
use df\core;
use df\apex;
use df\arch;
use df\fire;
use df\aura;

class ContentBlock extends arch\form\Delegate implements 
    arch\form\IInlineFieldRenderableDelegate, 
    arch\form\IResultProviderDelegate {

    use arch\form\TForm_InlineFieldRenderableDelegate;
    use arch\form\TForm_RequirableDelegate;

    protected $_block;
    protected $_isNested = false;
    protected $_category;
    protected $_defaultType;
    protected $_manager;

    protected function afterConstruct() {
        $this->_manager = fire\Manager::getInstance();
    }

    protected function init() {
        $this->_getAvailableBlockTypes();

        if(!$this->_block && ($type = $this->_state->getStore('blockType'))) {
            $this->_block = fire\block\Base::factory($type)->isNested($this->_isNested);
        }
    }

    protected function _getAvailableBlockTypes() {
        if(!$this->_state->hasStore('availableBlockTypes')) {
            $types = $this->_manager->getCategoryBlockNamesByFormat($this->_category);
            $this->_state->setStore('availableBlockTypes', $types);

            $count = 0;
            $default = null;

            foreach($types as $format => $set) {
                foreach($set as $id => $name) {
                    if($default === null) {
                        $default = $id;
                    }

                    $count++;
                }
            }

            $this->_state->setStore('availableBlockCount', $count);

            if(!$this->_state->hasStore('blockType') && $count == 1) {
                $this->_state->setStore('blockType', $default);
            }
        }

        return $this->_state->getStore('availableBlockTypes');
    }

    public function isNested($flag=null) {
        if($flag !== null) {
            $this->_isNested = (bool)$flag;
            return $this;
        }

        return $this->_isNested;
    }

    public function setBlock(fire\block\IBlock $block=null) {
        if($block !== null) {
            $this->setBlockType($block);
        } else {
            $this->_block = null;
        }

        return $this;
    }

    public function getBlock() {
        return $this->_block;
    }

    public function setDefaultType($type) {
        $this->_defaultType = $type;
        return $this;
    }

    public function getDefaultType() {
        return $this->_defaultType;
    }

    public function setBlockType($type) {
        if($type === null) {
            $this->_state->removeStore('blockType');
        } else {
            $this->_block = fire\block\Base::factory($type)->isNested($this->_isNested);
            $this->_state->setStore('blockType', $this->_block->getName());
        }

        return $this;
    }

    public function getBlockType() {
        return $this->_state->getStore('blockType');
    }

    public function setCategory($category) {
        $this->_category = $this->_manager->getCategory($category);

        return $this;
    }

    public function getCategory() {
        return $this->_category;
    }

    protected function loadDelegates() {
        if(!$this->_block) {
            if($this->_defaultType) {
                $this->setBlockType($this->_defaultType);
            } else if($this->_category) {
                $this->setBlockType($this->_category->getDefaultEditorBlockType());
            }
        }

        if($this->_block) {
            $type = $this->_block->getFormDelegateName();
            $this->loadDelegate('block', '~/nightfire/#/blocks/'.$type)
                ->setBlock($this->_block)
                ->isRequired($this->_isRequired);
        }
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fa) {
        $fa->setId($this->elementId('block'));
        $fa->push($this->html('<div class="fire-block">'));

        if($this->values->content->hasErrors()) {
            $fa->push($this->html->fieldError($this->values->content));
        }

        $available = $this->_getAvailableBlockTypes();
        $availableCount = $this->_state->getStore('availableBlockCount');
        $this->values->blockType->setValue($this->_block ? $this->_block->getName() : null);

        if($availableCount > 1) {
            $fa->push(
                $this->html->groupedSelectList(
                        $this->fieldName('blockType'),
                        $this->values->blockType,
                        $available
                    )
                    ->setNoSelectionLabel($this->_('-- select format --')),

                $this->html->eventButton(
                        $this->eventName('selectBlockType'),
                        $this->_block ? $this->_('Change format') : $this->_('Set format')
                    )
                    ->setIcon($this->_block ? 'refresh' : 'tick')
                    ->setDisposition($this->_block ? 'operative' : 'positive')
                    ->shouldValidate(false)
            );

            if($this->_block) {
                $fa->push($this->html('<br />'));
            }
        }


        if($this->_block) {
            $this->getDelegate('block')->renderFieldAreaContent($fa);
        }

        $fa->push($this->html('</div>'));
    }

    protected function onSelectBlockTypeEvent() {
        $type = $this->values['blockType'];

        if($type == '--' || empty($type)) {
            $type = null;
        }

        $this->getDelegate('block')->apply();
        $oldBlock = $this->_block;

        try {
            $this->setBlockType($type);
        } catch(\Exception $e) {
            $this->values->blockType->addError('type', $e->getMessage());
        }

        $this->unloadDelegate('block');

        if($oldBlock && $oldBlock !== $this->_block) {
            $this->_block->setTransitionValue($oldBlock->getTransitionValue());
            
            $this->loadDelegate('block', '~/nightfire/#/blocks/'.$this->_block->getFormDelegateName())
                ->setBlock($this->_block)
                ->isRequired($this->_isRequired)
                ->isNested($this->_isNested)
                ->initialize();
        }

        return $this->http->redirect('#'.$this->elementId('block'));
    }

    public function apply() {
        if(!$this->_block) {
            if($this->_isRequired) {
                $this->values->content->addError('required', $this->_(
                    'This field cannot be empty'
                ));
            }

            return null;
        }

        $delegate = $this->getDelegate('block');
        $delegate->apply();

        if(!$this->_isRequired && $this->_block->isEmpty()) {
            return null;
        }

        return $this->_block;
    }
}