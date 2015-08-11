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
    
class BlockLink extends Link {
    
    const PRIMARY_TAG = 'a';

    public function __construct(arch\IContext $context, $uri, $body=null, $description=null, $matchRequest=null) {
        parent::__construct($context, $uri, $body, $matchRequest);

        if($description !== null) {
            $this->setDescription($description);
        }
    }

    protected function _render() {
        $body = $this->_body;
        $description = $this->_description;
        $iconName = $this->_icon;
        
        $icon = $note = null;

        if($this->_icon) {
            $icon = $this->_renderTarget->getView()->html->icon($this->_icon);
        }

        if($this->_note !== null) {
            $note = $this->_note;
            $this->_note = null;

            $body = [
                $body,
                new aura\html\Element('sup', $note)
            ];
        }

        $body = [new aura\html\Element('header', [$icon, $body])];

        if($this->_showDescription) {
            $body[] = new aura\html\Element('p', $description, ['class' => 'description']);
        }

        $this->setBody($body);

        $this->_icon = null;
        $this->_description = null;

        $output = parent::_render();

        $this->_description = $description;
        $this->_body = $body;
        $this->_icon = $iconName;
        $this->_note = $note;

        return $output;
    }
}