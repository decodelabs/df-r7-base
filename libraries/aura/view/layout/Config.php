<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\layout;

use df;
use df\core;
use df\aura;
use df\arch;
    
class Config extends core\Config implements IConfig {

    const ID = 'layouts';

    protected static $_staticLayouts = [
    	'Default' => [
            'name' => 'Standard layout',
            'areas' => null,
            'slots' => [
                'primary' => [
                    'name' => 'Main content',
                    'minBlocks' => 0,
                    'maxBlocks' => null,
                    'blockTypes' => null
                ]
            ]
        ]
	];

	public function getDefaultValues() {
		return [];
	}

	public function getLayoutList($area=null) {
		$output = array();

		foreach(self::$_staticLayouts as $id => $set) {
			$output[$id] = $set['name'];
		}

		foreach($this->_values as $id => $set) {
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

			if(isset($set['name'])) {
				$output[$id] = $set['name'];
			} else {
				$output[$id] = $id;
			}
		}

		ksort($output);
		return $output;
	}

	public function getLayoutDefinition($id) {
		if(isset(self::$_staticLayouts[$id])) {
			$output = $this->getStaticLayoutDefinition($id);
			
			$data = new core\collection\Tree(
				isset($this->_values[$id]) ?
					$this->_values[$id] :
					null
			);

			if(isset($data->name)) {
				$output->setName($data['name']);
			}
		} else {
			if(!isset($this->_values[$id])) {
				return null;
			}

			$data = new core\collection\Tree(
				isset($this->_values[$id]) ?
					$this->_values[$id] :
					null
			);

			$output = new LayoutDefinition($id, $data->get('name', $id));
			$output->setAreas($data->areas->toArray());
		}

		if(!count($data->slots)) {
			$data->slots = self::$_staticLayouts['Default']['slots'];
		}

		foreach($data->slots as $slotId => $slotData) {
			$output->addSlot(
				(new SlotDefinition($slotId, $slotData->get('name', $slotId)))
					->setMinBlocks($slotData->get('minBlocks', 0))
					->setMaxBlocks($slotData->get('maxBlocks', null))
					->setBlockTypes($slotData->blockTypes->toArray())
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
		$output = new LayoutDefinition($id, $data->get('name', $id), true);

		foreach($data->slots as $slotId => $slotData) {
			$output->addSlot(
				(new SlotDefinition($slotId, $slotData->get('name', $slotId)))
					->setMinBlocks($slotData->get('minBlocks', 0))
					->setMaxBlocks($slotData->get('maxBlocks', null))
					->setBlockTypes($slotData->blockTypes->toArray())
			);
		}

		return $output;
	}

	public function getAllLayoutDefinitions() {
		$output = array();

		$ids = array_unique(
			array_merge(
				array_keys(self::$_staticLayouts),
				array_keys($this->_values)
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

	public function setLayoutDefinition(ILayoutDefinition $definition) {
		$id = $definition->getId();

		if(isset(self::$_staticLayouts[$id])) {
			return $this->_setStaticLayoutDefinition($definition);
		}

		$this->removeLayoutDefinition($id);
		$slots = array();

		foreach($definition->getSlots() as $slotId => $slot) {
			$slots[$slotId] = [
				'name' => $slot->getName(),
				'minBlocks' => $slot->getMinBlocks(),
				'maxBlocks' => $slot->getMaxBlocks(),
				'blockTypes' => $slot->getBlockTypes()
			];
		}

		$this->_values[$id] = [
			'name' => $definition->getName(),
			'areas' => $definition->getAreas(),
			'slots' => $slots
		];

		return $this;
	}

	protected function _setStaticLayoutDefinition(ILayoutDefinition $definition) {
		$id = $definition->getId();
		$this->removeLayoutDefinition($id);
		$slots = array();

		foreach($definition->getSlots() as $slotId => $slot) {
			if(isset(self::$_staticLayouts[$id]['slots'][$slotId])) {
				continue;
			}

			$slots[$id] = [
				'name' => $slot->getName(),
				'minBlocks' => $slot->getMinBlocks(),
				'maxBlocks' => $slot->getMaxBlocks(),
				'blockTypes' => $slot->getBlockTypes()
			];
		}

		$this->_values[$id] = [
			'name' => $definition->getName(),
			'slots' => $slots
		];

		return $this;
	}

	public function removeLayoutDefinition($id) {
		if($id instanceof ILayoutDefinition) {
			$id = $id->getId();
		}

		unset($this->_values[$id]);
		return $this;
	}
}