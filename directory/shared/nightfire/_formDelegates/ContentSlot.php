<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\shared\nightfire\_formDelegates;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\BlockDelegate;

use DecodeLabs\Tagged as Html;
use df\arch;
use df\aura;
use df\core;
use df\fire;

class ContentSlot extends arch\node\form\Delegate implements
    arch\node\IInlineFieldRenderableDelegate,
    arch\node\IResultProviderDelegate
{
    use arch\node\TForm_InlineFieldRenderableDelegate;
    use core\constraint\TRequirable;

    protected $_slotDefinition;
    protected $_isNested = false;
    protected $_blockLabel = null;
    protected $_blocks = [];
    protected $_defaultBlockType;
    protected $_manager;

    protected function afterConstruct(): void
    {
        $this->_manager = fire\Manager::getInstance();
    }

    protected function init(): void
    {
        $this->getSlotDefinition();

        if (empty($this->_blocks)) {
            $this->_prepareBlockList();
        }
    }

    public function reloadDefaultValues(): void
    {
        $this->_delegates = [];

        parent::reloadDefaultValues();
    }

    protected function _prepareBlockList(array $types = null)
    {
        if ($types === null) {
            $types = $this->_state->getStore('blockTypes', []);
        }

        if (empty($types) && ($this->_defaultBlockType || $this->_isRequired)) {
            $default = $this->_defaultBlockType ? ucfirst($this->_defaultBlockType) : null;

            if (!$default || !$this->_manager->isBlockAvailable($default)) {
                $default = null;
                $category = $this->_manager->getCategory($this->_slotDefinition->getCategory());

                if ($category) {
                    $default = $category->getDefaultEditorBlockType();
                }
            }

            if ($default) {
                $types = ['block-1' => $default];
                $this->_state->setStore('blockTypes', $types);
            }
        }


        foreach ($types as $delegateId => $type) {
            try {
                $this->_blocks[$delegateId] = BlockAbstract::factory($type)
                    ->setNested($this->_isNested);
            } catch (Exceptional\NotFoundException $e) {
            }
        }
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



    public function setDefaultBlockType(string $type = null)
    {
        $this->_defaultBlockType = $type;
        return $this;
    }

    public function getDefaultBlockType()
    {
        return $this->_defaultBlockType;
    }


    // Slot definition
    public function setCategory(string $category)
    {
        $this->getSlotDefinition()->setCategory($category);
        return $this;
    }

    public function setSlotDefinition(fire\ISlotDefinition $slotDefinition)
    {
        $this->_slotDefinition = $slotDefinition;
        return $this;
    }

    public function getSlotDefinition()
    {
        if (!$this->_slotDefinition) {
            $this->_slotDefinition = fire\slot\Definition::createDefault();
        }

        return $this->_slotDefinition;
    }


    // Slot content
    public function setSlotContent(fire\ISlotContent $slotContent = null)
    {
        if ($slotContent !== null) {
            $slotContent = clone $slotContent;
            $types = [];
            $counter = 1;

            foreach ($slotContent->getBlocks() as $block) {
                $delegateId = 'block-' . $counter++;
                $types[$delegateId] = $block->getName();
                $this->_blocks[$delegateId] = clone $block;
            }

            $this->_state->setStore('blockTypes', $types);
        }

        return $this;
    }

    public function getSlotContent()
    {
        return $this->apply();
    }

    // Block label
    public function setBlockLabel(string $label = null)
    {
        $this->_blockLabel = $label;
        return $this;
    }

    public function getBlockLabel()
    {
        return $this->_blockLabel;
    }

    // Block types
    protected function _getAvailableBlockTypes()
    {
        if (!$this->_state->hasStore('availableBlockTypes')) {
            if ($category = $this->_slotDefinition->getCategory()) {
                $types = $this->_manager->getCategoryBlockNamesByFormat(
                    $this->_slotDefinition->getCategory()
                );
            } else {
                $types = $this->_manager->getAllBlockNamesByFormat();
            }

            $this->_state->setStore('availableBlockTypes', $types);

            $count = 0;

            foreach ($types as $format => $set) {
                $count += count($set);
            }

            $this->_state->setStore('availableBlockCount', $count);
        }

        return $this->_state->getStore('availableBlockTypes');
    }


    // Delegates
    protected function loadDelegates(): void
    {
        foreach ($this->_blocks as $delegateId => $block) {
            $this->proxyLoadDelegate($delegateId, $block)
                ->as(BlockDelegate::class)
                ->isRequired($this->_isRequired)
                ->setNested($this->_isNested);
        }
    }

    // Render
    public function renderFieldContent(aura\html\widget\Field $container): void
    {
        $container->isRequired($this->isRequired());
        $available = $this->_getAvailableBlockTypes();
        $availableCount = $this->_state->getStore('availableBlockCount');

        $blockCount = count($this->_blocks);
        $counter = 1;
        $topKey = 0;

        foreach ($this->_blocks as $delegateId => $block) {
            $parts = explode('-', $delegateId, 2);
            $key = array_pop($parts);
            $blockName = $block->getName();

            if ($key > $topKey) {
                $topKey = $key;
            }

            if ($this->_blockLabel !== null) {
                $fa = $container->addField($this->_($this->_blockLabel, ['%n%' => $key]));
            } else {
                $fa = $container->addField();
            }

            $fa->setId($this->elementId($delegateId));
            $fa->push(Html::raw('<div class="fire-block">'));

            $this->values->blockType->{$delegateId}->setValue($blockName);

            $fa->add('nav.buttons', [
                Html::{'div.type'}([
                    $this->html->groupedSelect(
                        $this->fieldName('blockType[' . $delegateId . ']'),
                        $this->values->blockType->{$delegateId},
                        $available
                    )
                        ->setNoSelectionLabel($this->_('-- select format --')),

                    $this->html->eventButton(
                        $this->eventName('selectBlockType', $delegateId),
                        $this->_('Change')
                    )
                        ->setIcon('refresh')
                        ->setDisposition('operative')
                        ->shouldValidate(false)
                ]),

                Html::{'div.control'}([
                    $this->html->eventButton(
                        $this->eventName('removeBlock', $delegateId),
                        $this->_('Remove')
                    )
                    ->setIcon('remove')
                    ->shouldValidate(false),

                    $this->html->eventButton(
                        $this->eventName('moveBlockUp', $delegateId),
                        $this->_('Up')
                    )
                    ->setIcon('arrow-up')
                    ->shouldValidate(false)
                    ->isDisabled($counter == 1)
                    ->setDisposition('transitive'),

                    $this->html->eventButton(
                        $this->eventName('moveBlockDown', $delegateId),
                        $this->_('Down')
                    )
                    ->setIcon('arrow-down')
                    ->shouldValidate(false)
                    ->isDisabled($counter == $blockCount)
                    ->setDisposition('transitive')
                ])
            ]);

            $this[$delegateId]->as(BlockDelegate::class)
                ->renderFieldContent($fa);

            $fa->push(Html::raw('</div>'));
            $counter++;
        }

        if (!$this->_slotDefinition->hasBlockLimit() || $blockCount < $this->_slotDefinition->getMaxBlocks()) {
            $container->addField()
                ->setId($this->elementId('add-selector'))
                ->push(
                    $this->html->groupedSelect(
                        $this->fieldName('newBlockType'),
                        $this->values->newBlockType,
                        $available
                    )
                        ->setNoSelectionLabel($this->_('-- select format --')),
                    $this->html->eventButton(
                        $this->eventName('addBlock'),
                        $this->_('Add block')
                    )
                        ->setIcon('add')
                        ->setDisposition('positive')
                        ->shouldValidate(false)
                );
        }
    }

    protected function onSelectBlockTypeEvent($delegateId)
    {
        $type = $this->values->blockType->{$delegateId};

        if ($type == '--' || empty($type)) {
            $type = null;
        }


        $types = $this->_state->getStore('blockTypes', []);

        if (!isset($types[$delegateId])) {
            $this->values->blockType->{$delegateId}->addError('delegate', $this->_(
                'Delegate %n% not found',
                ['%n%' => $delegateId]
            ));

            return;
        }

        $this[$delegateId]->as(BlockDelegate::class)
            ->apply();

        try {
            $block = BlockAbstract::factory($type);
        } catch (\Throwable $e) {
            $this->values->blockType->{$delegateId}->addError('type', $e->getMessage());
            return;
        }

        if ($types[$delegateId] != $block->getName()) {
            $this->unloadDelegate($delegateId);
            $types[$delegateId] = $block->getName();

            if (isset($this->_blocks[$delegateId])) {
                $oldBlock = $this->_blocks[$delegateId];
                $block->setTransitionValue($oldBlock->getTransitionValue());
                $this->_blocks[$delegateId] = $block;

                $this->proxyLoadDelegate($delegateId, $block)
                    ->as(BlockDelegate::class)
                    ->isRequired($this->_isRequired)
                    ->setNested($this->_isNested)
                    ->initialize();
            }
        }

        $this->_state->setStore('blockTypes', $types);
        return Legacy::$http->redirect('#' . $this->elementId($delegateId));
    }

    protected function onAddBlockEvent()
    {
        $type = $this->values['newBlockType'];

        if ($type == '--' || empty($type)) {
            $type = null;
        }

        if ($type === null) {
            $this->values->newBlockType->addError('type', $this->_('Please select a type'));
            return;
        }

        try {
            $block = BlockAbstract::factory($type);
        } catch (\Throwable $e) {
            $this->values->newBlockType->addError('type', $e->getMessage());
            return;
        }

        $delegateId = $types = $this->_state->getStore('blockTypes', []);

        if (empty($delegateId)) {
            $delegateId = 'block-1';
        } else {
            krsort($delegateId, \SORT_NATURAL);
            $delegateId = (string)array_keys($delegateId)[0];

            $parts = explode('-', $delegateId, 2);
            $key = array_pop($parts);
            $delegateId = 'block-' . ++$key;
        }

        $types[$delegateId] = $block->getName();
        $this->_state->setStore('blockTypes', $types);
        return Legacy::$http->redirect('#' . $this->elementId($delegateId));
    }

    protected function onRemoveBlockEvent($delegateId)
    {
        $types = $this->_state->getStore('blockTypes', []);

        unset($types[$delegateId]);
        $this->unloadDelegate($delegateId);

        $this->_state->setStore('blockTypes', $types);
        return Legacy::$http->redirect('#' . $this->elementId('add-selector'));
    }

    protected function onMoveBlockUpEvent($delegateId)
    {
        $types = $this->_state->getStore('blockTypes', []);
        $newTypes = [];
        $lastKey = $lastValue = null;

        foreach ($types as $key => $value) {
            if ($key == $delegateId) {
                array_pop($newTypes);
                $newTypes[$key] = $value;

                if ($lastKey !== null) {
                    $newTypes[$lastKey] = $lastValue;
                }
            } else {
                $newTypes[$key] = $value;
            }

            $lastKey = $key;
            $lastValue = $value;
        }

        $this->_state->setStore('blockTypes', $newTypes);
        return Legacy::$http->redirect('#' . $this->elementId($delegateId));
    }

    protected function onMoveBlockDownEvent($delegateId)
    {
        $types = $this->_state->getStore('blockTypes', []);
        $newTypes = [];
        $buffer = null;

        foreach ($types as $key => $value) {
            if ($key == $delegateId) {
                $buffer = $value;
                continue;
            }

            $newTypes[$key] = $value;

            if ($buffer !== null) {
                $newTypes[$delegateId] = $buffer;
                $buffer = null;
            }
        }

        if ($buffer !== null) {
            $newTypes[$delegateId] = $buffer;
        }

        $this->_state->setStore('blockTypes', $newTypes);
        return Legacy::$http->redirect('#' . $this->elementId($delegateId));
    }



    public function apply(): ?fire\slot\Content
    {
        $isEmpty = true;

        foreach ($this->_blocks as $delegateId => $block) {
            $this[$delegateId]->as(BlockDelegate::class)
                ->apply();

            if ($isEmpty && !$block->isEmpty()) {
                $isEmpty = false;
            }

            $type = $this->values->blockType[$delegateId];

            if (
                !strlen((string)$type) ||
                $type == $block->getName()
            ) {
                continue;
            }

            $this->onSelectBlockTypeEvent($delegateId);

            $this[$delegateId]->as(BlockDelegate::class)
                ->apply();
        }

        $output = new fire\slot\Content($this->_slotDefinition->getId());

        if ($isEmpty) {
            if ($this->_isRequired) {
                $this->values->newBlockType->addError('required', $this->_('Please enter some content'));
                return $output;
            }

            return null;
        }

        $blockCount = count($this->_blocks);
        $minBlocks = $this->_slotDefinition->getMinBlocks();
        $maxBlocks = $this->_slotDefinition->getMaxBlocks();

        if ($blockCount < $minBlocks) {
            $this->values->newBlockType->addError('min', $this->_(
                [
                    '1' => 'This slot requires at least 1 block',
                    '*' => 'This slot requires at least %n% blocks'
                ],
                ['%n%' => $minBlocks],
                $minBlocks
            ));
        }

        if ($this->_slotDefinition->hasBlockLimit() && $blockCount > $maxBlocks) {
            $this->values->newBlockType->addError('max', $this->_(
                [
                    '1' => 'This slot can have a maximum of 1 block',
                    '*' => 'This slot can have a maximum of %n% blocks'
                ],
                ['%n%' => $maxBlocks],
                $maxBlocks
            ));
        }

        $output->setBlocks($this->_blocks);
        return $output;
    }
}
