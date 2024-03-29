<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\form;

use DecodeLabs\Dictum;
use DecodeLabs\R7\Legacy;
use df\arch;

use df\core;
use df\link;

class EventDescriptor implements arch\node\IFormEventDescriptor
{
    protected $_target;
    protected $_eventName;
    protected $_eventArgs;

    protected $_redirect;
    protected $_forceRedirect = false;
    protected $_reload = false;
    protected $_response;

    protected $_successCallback;
    protected $_failureCallback;

    public static function factory($output): arch\node\IFormEventDescriptor
    {
        if ($output instanceof arch\node\IFormEventDescriptor) {
            return $output;
        }

        return new self($output);
    }

    public function __construct($output = null)
    {
        if ($output !== null) {
            $this->parseOutput($output);
        }
    }


    public function parseOutput($output)
    {
        if ($output === null || $output === $this) {
            return $this;
        }

        // Response
        if (is_string($output)
        || $output instanceof link\http\IResponse
        || $output instanceof link\http\IUrl
        || $output instanceof arch\IRequest) {
            return $this->setResponse($output);
        }

        // Callback
        if (is_callable($output)) {
            return $this->setSuccessCallback($output);
        }

        // Handler class
        if (core\lang\Util::isAnonymousObject($output)
        || is_array($output)) {
            $this->_parseAnonymous($output);
            return $this;
        }

        // What else?
        return $this->setResponse($output);
    }

    protected function _parseAnonymous($output)
    {
        $attrs = [
            'redirect' => 'setRedirect',
            'forceRedirect' => 'shouldForceRedirect',
            'response' => 'setResponse',
            'reload' => 'shouldReload'
        ];

        if (is_object($output)) {
            if (method_exists($output, 'onSuccess')) {
                $this->setSuccessCallback([$output, 'onSuccess']);
            }

            if (method_exists($output, 'onFailure')) {
                $this->setFailureCallback([$output, 'onFailure']);
            }

            $class = get_class($output);

            foreach ($attrs as $attr => $method) {
                $upperAttr = Dictum::constant($attr);

                if (property_exists($output, $attr)) {
                    $this->{$method}($output->{$attr});
                } elseif (defined($class . '::' . $upperAttr)) {
                    $this->{$method}(constant($class . '::' . $upperAttr));
                }
            }
        } elseif (is_array($output)) {
            if (isset($output['onSuccess'])) {
                $this->setSuccessCallback($output['onSuccess']);
            }

            if (isset($output['onFailure'])) {
                $this->setFailureCallback($output['onFailure']);
            }

            foreach ($attrs as $attr => $method) {
                if (array_key_exists($attr, $output)) {
                    $this->{$method}($output[$attr]);
                }
            }
        }
    }


    // Target
    public function setTarget(/*string?*/ $target)
    {
        $this->_target = $target;
        return $this;
    }

    public function getTarget()
    {
        return $this->_target;
    }

    public function setEventName(string $name)
    {
        $this->_eventName = $name;
        return $this;
    }

    public function getEventName()
    {
        return $this->_eventName;
    }

    public function getFullEventName()
    {
        if (!$this->_eventName) {
            return null;
        }

        if ($output = $this->_target) {
            $output .= '.';
        }

        return $output . $this->_eventName;
    }

    public function getFullEventCall()
    {
        if (!$this->_eventName) {
            return null;
        }

        return $this->getFullEventName() . '(' . implode(',', (array)$this->getEventArgs()) . ')';
    }

    public function setEventArgs(array $args)
    {
        $this->_eventArgs = $args;
        return $this;
    }

    public function getEventArgs()
    {
        return $this->_eventArgs;
    }



    // Callbacks
    public function setSuccessCallback($callback)
    {
        $this->_successCallback = core\lang\Callback::factory($callback);
        return $this;
    }

    public function getSuccessCallback()
    {
        return $this->_successCallback;
    }

    public function triggerSuccess(arch\node\IForm $form)
    {
        return $this->_normalizeCallbackOutput(
            core\lang\Callback::call($this->_successCallback, $form)
        );
    }

    public function setFailureCallback($callback)
    {
        $this->_failureCallback = core\lang\Callback::factory($callback);
        return $this;
    }

    public function getFailureCallback()
    {
        return $this->_failureCallback;
    }

    public function triggerFailure(arch\node\IForm $form)
    {
        return $this->_normalizeCallbackOutput(
            core\lang\Callback::call($this->_failureCallback, $form)
        );
    }

    protected function _normalizeCallbackOutput($output)
    {
        if (is_string($output)) {
            $this->setRedirect($output);
            $output = null;
        } elseif (!is_bool($output)) {
            $this->parseOutput($output);
            $output = null;
        }

        return $output;
    }


    // Redirect
    public function setRedirect($redirect)
    {
        if ($redirect instanceof link\http\response\Redirect) {
            $redirect = $redirect->getUrl();
        }

        if (is_string($redirect)) {
            $redirect = Legacy::getContext()->uri->directoryRequest($redirect);
        }

        if ($redirect instanceof link\http\IUrl) {
            $redirect = $redirect->getDirectoryRequest() ?? $redirect;
        }

        if ($redirect instanceof link\http\IUrl
        || $redirect instanceof arch\IRequest) {
            $this->_redirect = $redirect;
        } else {
            $this->_redirect = null;
        }

        return $this;
    }

    public function getRedirect()
    {
        return $this->_redirect;
    }

    public function hasRedirect(): bool
    {
        return $this->_redirect !== null;
    }

    public function shouldForceRedirect(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_forceRedirect = $flag;
            return $this;
        }

        return $this->_forceRedirect;
    }

    public function shouldReload(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_reload = $flag;
            return $this;
        }

        return $this->_reload;
    }

    // Response
    public function setResponse($response)
    {
        $this->_response = $response;

        if ($response instanceof link\http\IResponse
        || $response instanceof link\http\IUrl
        || $response instanceof arch\IRequest) {
            $this->setRedirect($response);
        }

        return $this;
    }

    public function getResponse()
    {
        if (
            $this->_response === null &&
            $this->hasRedirect()
        ) {
            $this->_response = Legacy::$http->redirect($this->_redirect);
        }

        return $this->_response;
    }

    public function hasResponse(): bool
    {
        return $this->_response !== null;
    }
}
