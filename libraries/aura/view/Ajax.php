<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;

class Ajax extends Base implements IAjaxView {

    use TResponseView;

    protected $_redirect;
    protected $_forceRedirect = false;
    protected $_isComplete = false;
    protected $_shouldReload = false;

    public function setRedirect($request) {
        $this->_redirect = $request;
        return $this;
    }

    public function getRedirect() {
        return $this->_redirect;
    }

    public function shouldForceRedirect(bool $flag=null) {
        if($flag !== null) {
            $this->_forceRedirect = $flag;
            return $this;
        }

        return $this->_forceRedirect;
    }

    public function isComplete(bool $flag=null) {
        if($flag !== null) {
            $this->_isComplete = $flag;
            return $this;
        }

        return $this->_isComplete;
    }

    public function shouldReload(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldReload = $flag;
            return $this;
        }

        return $this->_shouldReload;
    }

    protected function _afterRender($output) {
        if(!is_array($output)) {
            $output = ['content' => (string)$output];
        }

        $output['node'] = $this->context->request->getLiteralPathString();

        if($this->_redirect !== null) {
            $output['redirect'] = $this->context->uri($this->_redirect);
        }

        if($this->_isComplete) {
            $output['complete'] = true;
        }

        if($this->_shouldReload) {
            $output['reload'] = true;
        }

        return $this->data->jsonEncode($output);
    }

    public function getContentType() {
        return 'application/json';
    }
}