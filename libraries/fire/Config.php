<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire;

use df;
use df\core;
use df\fire;
    
class Config extends core\Config {

    const ID = 'nightfire';

    protected static $_staticLayouts = [
        'Default' => [
            'name' => 'Standard layout',
            'areas' => null,
            'slots' => [
                'primary' => [
                    'name' => 'Main content',
                    'minBlocks' => 0,
                    'maxBlocks' => null,
                    'category' => null
                ]
            ]
        ]
    ];

    public function getDefaultValues() {
        return [
            'categories' => [],
            'layouts' => []
        ];
    }



// Categories
    public function getCategoryAugmentations() {
        $output = [];

        foreach($this->values->categories as $name => $blocks) {
            foreach($blocks as $blockName => $enabled) {
                $output[$name][$blockName] = (bool)$enabled->getValue();
            }
        }

        return $output;
    }



// Layouts
    public function getLayoutList($area=null) {
        $output = [];

        foreach(self::$_staticLayouts as $id => $set) {
            $output[$id] = $set['name'];
        }

        foreach($this->values->layouts as $id => $set) {
            if(isset(self::$_staticLayouts[$id])) {
                $output[$id] = isset(self::$_staticLayouts[$id]['name']) ?
                    self::$_staticLayouts[$id]['name'] :
                    $id;

                continue;
            }

            if($area !== null) {
                $definition = $this->getLayoutDefinition($id);

                if(!$definition->hasArea($area)) {
                    continue;
                }
            }

            $output[$id] = $set->get('name', $id);
        }

        ksort($output);
        return $output;
    }

    public function getLayoutDefinition($id) {
        $data = $this->values->layouts->{$id};

        if(isset(self::$_staticLayouts[$id])) {
            $output = $this->getStaticLayoutDefinition($id);
            
            if(isset($data->name)) {
                $output->setName($data['name']);
            }
        } else {
            if($data->isEmpty()) {
                return null;
            }

            $output = new fire\layout\Definition($id, $data->get('name', $id));
            $output->setAreas($data->areas->toArray());
        }

        if($data->slots->isEmpty()) {
            $data->slots = self::$_staticLayouts['Default']['slots'];
        }

        foreach($data->slots as $slotId => $slotData) {
            $output->addSlot(
                (new fire\slot\Definition($slotId, $slotData->get('name', $slotId)))
                    ->setMinBlocks($slotData->get('minBlocks', 0))
                    ->setMaxBlocks($slotData->get('maxBlocks', null))
                    ->setCategory($slotData['category'])
            );
        }

        return $output;
    }

    public function isStaticLayout($id) {
        return isset(self::$_staticLayouts[$id]);
    }

    public function getStaticLayoutDefinition($id) {
        if(!isset(self::$_staticLayouts[$id])) {
            $id = 'Default';
        }

        $data = new core\collection\Tree(self::$_staticLayouts[$id]);
        $output = new fire\layout\Definition($id, $data->get('name', $id), true);

        foreach($data->slots as $slotId => $slotData) {
            $output->addSlot(
                (new fire\slot\Definition($slotId, $slotData->get('name', $slotId)))
                    ->setMinBlocks($slotData->get('minBlocks', 0))
                    ->setMaxBlocks($slotData->get('maxBlocks', null))
                    ->setCategory($slotData['category'])
            );
        }

        return $output;
    }

    public function getAllLayoutDefinitions() {
        $output = [];

        $ids = array_unique(
            array_merge(
                array_keys(self::$_staticLayouts),
                $this->values->layouts->getKeys()
            )
        );

        foreach($ids as $id) {
            if(isset($output[$id])) {
                continue;
            }

            $output[$id] = $this->getLayoutDefinition($id);
        }

        ksort($output);
        return $output;
    }

    public function setLayoutDefinition(fire\layout\IDefinition $definition) {
        $id = $definition->getId();

        if(isset(self::$_staticLayouts[$id])) {
            return $this->_setStaticLayoutDefinition($definition);
        }

        $this->removeLayoutDefinition($id);
        $slots = [];

        foreach($definition->getSlots() as $slotId => $slot) {
            $slots[$slotId] = [
                'name' => $slot->getName(),
                'minBlocks' => $slot->getMinBlocks(),
                'maxBlocks' => $slot->getMaxBlocks(),
                'category' => $slot->getCategory()
            ];
        }

        $this->values->layouts->{$id} = [
            'name' => $definition->getName(),
            'areas' => $definition->getAreas(),
            'slots' => $slots
        ];

        return $this;
    }

    protected function _setStaticLayoutDefinition(fire\layout\IDefinition $definition) {
        $id = $definition->getId();
        $this->removeLayoutDefinition($id);
        $slots = [];

        foreach($definition->getSlots() as $slotId => $slot) {
            if(isset(self::$_staticLayouts[$id]['slots'][$slotId])) {
                //continue;
            }

            $slots[$id] = [
                'name' => $slot->getName(),
                'minBlocks' => $slot->getMinBlocks(),
                'maxBlocks' => $slot->getMaxBlocks(),
                'category' => $slot->getCategory()
            ];
        }

        $this->values->layouts->{$id} = [
            'name' => $definition->getName(),
            'slots' => $slots
        ];

        return $this;
    }

    public function removeLayoutDefinition($id) {
        if($id instanceof fire\layout\IDefinition) {
            $id = $id->getId();
        }

        unset($this->values->layouts->{$id});
        return $this;
    }
}