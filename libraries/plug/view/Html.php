<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\aura;
use df\arch;

class Html implements aura\view\IHelper {
    
    protected $_view;
    
    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
    }
    
    public function __call($member, $args) {
        return aura\html\widget\Base::factory($this->_view->getContext(), $member, $args)->setRenderTarget($this->_view);
    }
    
    public function string($value) {
        return new aura\html\ElementString($value);
    }
    
    public function tag($name, array $attributes=array()) {
        return new aura\html\Tag($name, $attributes);
    }
    
    public function element($name, $content=null, array $attributes=array()) {
        return new aura\html\Element($name, $content, $attributes);
    }



// Compound widget shortcuts
    public function icon($name, $body=null) {
        $iconChar = $this->_view->getTheme()->mapIcon($name);

        if($iconChar === null) {
            return null;
        }

        return new aura\html\Element('span', $body, [
            'aria-hidden' => 'true',
            'data-icon' => new aura\html\ElementString($iconChar)
        ]);
    }

    public function backLink($default=null, $success=true) {
        return $this->link(
                $this->_view->uri->back($default, $success),
                $this->_view->_('Back')
            )
            ->setIcon('back');
    }
}
