<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Coercion;

use df\aura;

class PanelSet extends Base
{
    public const PRIMARY_TAG = 'div.list.panels';

    protected $_panels = [];

    protected function _render()
    {
        if (empty($this->_panels)) {
            return '';
        }

        $cells = [];

        foreach ($this->_panels as $id => $panel) {
            $cellTag = new aura\html\Tag('article', [
                'class' => 'w panel field-' . $id,
            ]);

            if ($width = $panel->getWidth()) {
                $cellTag->setStyle('flex-basis', $width . '%');
            }

            $bodyTag = new aura\html\Tag('div', ['class' => 'body']);
            $cells[] = $cellTag->renderWith($bodyTag->renderWith($panel->getBody()));
        }

        return $this->getTag()->renderWith($cells);
    }



    public function addPanel($a, $b = null, $c = null)
    {
        if ($c !== null) {
            $id = $a;
            $width = $b;
            $content = $c;
        } elseif ($b !== null) {
            if (is_numeric($a)) {
                $id = null;
                $width = $a;
            } else {
                $id = $a;
                $width = null;
            }

            $content = $b;
        } else {
            $id = null;
            $width = null;
            $content = $a;
        }

        $panel = (new PanelSet_Panel($id))
            ->setWidth($width)
            ->setBody($content);


        $this->_panels[$panel->getId()] = $panel;
        return $this;
    }

    public function removePanel(string $id)
    {
        unset($this->_panels[$id]);
        return $this;
    }
}



// Panel
class PanelSet_Panel
{
    protected $_id;
    protected $_width;
    protected $_body;

    public function __construct(?string $id)
    {
        if ($id === null) {
            $id = 'panel' . uniqid();
        }

        $this->setId($id);
    }

    public function setId(string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function setWidth($width)
    {
        if (substr((string)$width, -1) == '%') {
            $width = substr((string)$width, 0, -1);
        }

        $this->_width = Coercion::clampFloat($width, 0.1, 100);
        return $this;
    }

    public function getWidth(): ?float
    {
        return $this->_width;
    }

    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    public function getBody()
    {
        return $this->_body;
    }
}
