<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\entry;

class Spacer extends Base
{
    public function getId(): ?string
    {
        if ($this->_id === null) {
            return $this->_id = 'spacer-' . md5(uniqid('spacer', true));
        }

        return parent::getId();
    }

    public function toArray(): array
    {
        return [
            'type' => 'Spacer',
            'weight' => $this->_weight,
            'id' => $this->_id
        ];
    }
}
