<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Dovetail\Repository;
use df\fire\ILayoutDefinition;
use df\fire\layout\Definition as LayoutDefinition;
use df\fire\slot\Definition as SlotDefinition;

class Nightfire implements Config
{
    use ConfigTrait;

    public const STATIC_LAYOUTS = [
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

    public static function getDefaultValues(): array
    {
        return [
            'categories' => [],
            'layouts' => []
        ];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getCategoryAugmentations(): array
    {
        $output = [];

        foreach ($this->data->categories as $name => $blocks) {
            foreach ($blocks as $blockName => $enabled) {
                $output[(string)$name][(string)$blockName] = (bool)$enabled->getValue();
            }
        }

        return $output;
    }



    /**
     * @return array<string, string>
     */
    public function getLayoutList(string $area = null): array
    {
        $output = [];

        foreach (self::STATIC_LAYOUTS as $id => $set) {
            $output[$id] = $set['name'];
        }

        foreach ($this->data->layouts as $id => $set) {
            $id = (string)$id;

            if (isset(self::STATIC_LAYOUTS[$id])) {
                $output[$id] = self::STATIC_LAYOUTS[$id]['name'];
                continue;
            }

            if ($area !== null) {
                $definition = $this->getLayoutDefinition($id);

                if (!$definition->hasArea($area)) {
                    continue;
                }
            }

            $output[$id] = $set->name->as('string', ['default' => $id]);
        }

        ksort($output);
        return $output;
    }

    public function getLayoutDefinition(string $id): ILayoutDefinition
    {
        /** @var Repository $data */
        $data = $this->data->layouts->{$id};

        if (isset(self::STATIC_LAYOUTS[$id])) {
            $output = $this->getStaticLayoutDefinition($id);

            if (isset($data->name)) {
                $output->setName($data->name->as('string'));
            }
        } else {
            $output = new LayoutDefinition($id, $data->name->as('string', ['default' => $id]));
            $output->setAreas($data->areas->toArray());
        }

        if ($data->slots->isEmpty()) {
            /** @phpstan-ignore-next-line */
            $data->slots = self::STATIC_LAYOUTS['Default']['slots'];
        }

        foreach ($data->slots as $slotId => $slotData) {
            $output->addSlot(
                (new SlotDefinition((string)$slotId, $slotData->name->as('string', ['default' => $slotId])))
                    ->setMinBlocks($slotData->minBlocks->as('int', ['default' => 0]))
                    ->setMaxBlocks($slotData->maxBlocks->as('?int'))
                    ->setCategory($slotData['category'])
            );
        }

        return $output;
    }

    public function isStaticLayout(string $id): bool
    {
        return isset(self::STATIC_LAYOUTS[$id]);
    }

    public function getStaticLayoutDefinition(string $id): ILayoutDefinition
    {
        if (!isset(self::STATIC_LAYOUTS[$id])) {
            $id = 'Default';
        }

        $data = new Repository(self::STATIC_LAYOUTS[$id]);

        $output = new LayoutDefinition(
            $id,
            $data->name->as('string', ['default' => $id]),
            true
        );

        /** @var Repository $slotData */
        foreach ($data->slots as $slotId => $slotData) {
            $output->addSlot(
                (new SlotDefinition((string)$slotId, $slotData->name->as('string', ['default' => $slotId])))
                    ->setMinBlocks($slotData->minBlocks->as('int', ['default' => 0]))
                    ->setMaxBlocks($slotData->maxBlocks->as('?int'))
                    ->setCategory($slotData->category->as('?string'))
            );
        }

        return $output;
    }

    /**
     * @return array<string, ILayoutDefinition>
     */
    public function getAllLayoutDefinitions(): array
    {
        $output = [];

        $ids = array_unique(
            array_merge(
                array_keys(self::STATIC_LAYOUTS),
                $this->data->layouts->getKeys()
            )
        );

        foreach ($ids as $id) {
            $id = (string)$id;

            if (isset($output[$id])) {
                continue;
            }

            $output[$id] = $this->getLayoutDefinition($id);
        }

        ksort($output);
        return $output;
    }
}
