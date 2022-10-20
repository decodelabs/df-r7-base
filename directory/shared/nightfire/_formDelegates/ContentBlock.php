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

use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockDelegate;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\Tagged as Html;

class ContentBlock extends arch\node\form\Delegate implements
    arch\node\IInlineFieldRenderableDelegate,
    arch\node\IResultProviderDelegate
{
    use arch\node\TForm_InlineFieldRenderableDelegate;
    use core\constraint\TRequirable;

    protected $_block;
    protected $_isNested = false;
    protected $_category;
    protected $_defaultType;
    protected $_manager;

    protected function afterConstruct()
    {
        $this->_manager = fire\Manager::getInstance();
    }

    protected function init()
    {
        $this->_getAvailableBlockTypes();

        if (!$this->_block && ($type = $this->_state->getStore('blockType'))) {
            $this->_block = BlockAbstract::factory($type)->setNested($this->_isNested);
        }
    }

    public function reloadDefaultValues(): void
    {
        if ($this->hasDelegate('block')) {
            /** @var BlockDelegate $delegate */
            $delegate = $this->proxyLoadDelegate('block', $this->_block);
            $delegate->isRequired($this->_isRequired);
        }

        parent::reloadDefaultValues();
    }

    protected function _getAvailableBlockTypes()
    {
        if (!$this->_state->hasStore('availableBlockTypes')) {
            if ($this->_category) {
                $types = $this->_manager->getCategoryBlockNamesByFormat($this->_category);
            } else {
                $types = $this->_manager->getAllBlockNamesByFormat();
            }

            $this->_state->setStore('availableBlockTypes', $types);

            $count = 0;
            $default = null;

            foreach ($types as $format => $set) {
                foreach ($set as $id => $name) {
                    if ($default === null) {
                        $default = $id;
                    }

                    $count++;
                }
            }

            $this->_state->setStore('availableBlockCount', $count);

            if (!$this->_state->hasStore('blockType') && $count == 1) {
                $this->_state->setStore('blockType', $default);
            }
        }

        return $this->_state->getStore('availableBlockTypes');
    }

    /**
     * Set nested
     */
    public function setNested(bool $nested): static
    {
        $this->_isNested = $nested;
        return $this;
    }

    /**
     * Is nested
     */
    public function isNested(): bool
    {
        return $this->_isNested;
    }



    public function setBlock(Block $block=null)
    {
        if ($block !== null) {
            $this->setBlockType($block);
        } else {
            $this->_block = null;
        }

        return $this;
    }

    public function getBlock()
    {
        return $this->_block;
    }

    public function setDefaultType(string $type=null)
    {
        $this->_defaultType = $type;
        return $this;
    }

    public function getDefaultType()
    {
        return $this->_defaultType;
    }

    public function setBlockType($type)
    {
        if ($type === null) {
            $this->_state->removeStore('blockType');
        } else {
            if (!$block = BlockAbstract::normalize($type)) {
                throw Exceptional::InvalidArgument(
                    'Cannot build block of type '.$type
                );
            }

            $block->setNested($this->_isNested);
            $this->_block = $block;
            $this->_state->setStore('blockType', $this->_block->getName());
        }

        return $this;
    }

    public function getBlockType()
    {
        return $this->_state->getStore('blockType');
    }

    public function setCategory($category)
    {
        $this->_category = $this->_manager->getCategory($category);

        return $this;
    }

    public function getCategory()
    {
        return $this->_category;
    }

    protected function loadDelegates()
    {
        if (!$this->_block) {
            if ($this->_defaultType) {
                $this->setBlockType($this->_defaultType);
            } elseif ($this->_category) {
                $this->setBlockType($this->_category->getDefaultEditorBlockType());
            }
        }

        if ($this->_block) {
            /** @var BlockDelegate */
            $delegate = $this->proxyLoadDelegate('block', $this->_block);
            $delegate->isRequired($this->_isRequired);
        }
    }

    public function renderFieldContent(aura\html\widget\Field $fa): void
    {
        $fa->setId($this->elementId('block'));
        $fa->push(Html::raw('<div class="fire-block">'));

        $available = $this->_getAvailableBlockTypes();
        $availableCount = $this->_state->getStore('availableBlockCount');
        $this->values->blockType->setValue($this->_block ? $this->_block->getName() : null);

        if ($availableCount > 1) {
            $fa->add('nav.buttons > div.type', [
                $this->html->groupedSelect(
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
            ]);
        }

        if ($this->values->content->hasErrors()) {
            $fa->push($this->html->fieldError($this->values->content));
        }

        if ($this->_block) {
            /** @var BlockDelegate */
            $delegate = $this['block'];
            $delegate->renderFieldContent($fa);
        }

        $fa->push(Html::raw('</div>'));
    }

    protected function onSelectBlockTypeEvent()
    {
        $type = $this->values['blockType'];

        if ($type == '--' || empty($type)) {
            $type = null;
        }

        if (isset($this['block'])) {
            /** @var BlockDelegate */
            $delegate = $this['block'];
            $delegate->apply();
        }

        $oldBlock = $this->_block;

        try {
            $this->setBlockType($type);
        } catch (\Throwable $e) {
            $this->values->blockType->addError('type', $e->getMessage());
        }

        if (isset($this['block'])) {
            $this->unloadDelegate('block');
        }

        if ($oldBlock && $oldBlock !== $this->_block) {
            $this->_block->setTransitionValue($oldBlock->getTransitionValue());

            /** @var BlockDelegate $delegate */
            $delegate = $this->proxyLoadDelegate('block', $this->_block);
            $delegate
                ->isRequired($this->_isRequired)
                ->setNested($this->_isNested)
                ->initialize();
        }

        return Legacy::$http->redirect('#'.$this->elementId('block'));
    }

    public function apply()
    {
        if (!$this->_block) {
            if ($this->_isRequired) {
                $this->values->content->addError('required', $this->_(
                    'This field cannot be empty'
                ));
            }

            return null;
        }

        /** @var BlockDelegate */
        $delegate = $this['block'];
        $delegate->apply();

        if (!$this->_isRequired && $this->_block->isEmpty()) {
            return null;
        }

        return $this->_block;
    }
}
