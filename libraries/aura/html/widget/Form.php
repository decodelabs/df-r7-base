<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Exceptional;

use df\arch;

class Form extends Container implements IFormWidget, IWidgetShortcutProvider
{
    use TWidget_VisualInput;
    use TWidget_TargetAware;

    public const PRIMARY_TAG = 'form';

    public const ENC_URLENCODED = 'application/x-www-form-urlencoded';
    public const ENC_MULTIPART = 'multipart/form-data';
    public const ENC_PLAINTEXT = 'text/plain';

    protected $_action;
    protected $_method = 'post';
    protected $_encoding;// = self::ENC_URLENCODED;
    protected $_name;
    protected $_acceptCharset = 'utf-8';

    public function __construct(arch\IContext $context, $action = null, $method = null, $encoding = null)
    {
        parent::__construct($context);

        $this->setAction($action);
        $this->setMethod($method);

        if ($encoding !== null) {
            $this->setEncoding($encoding);
        }
    }

    protected function _render()
    {
        $children = $this->_prepareChildren();
        $tag = $this->getTag();

        $tag->addAttributes([
            'action' => $this->_context->uri->__invoke($this->_action),
            'method' => $this->_method
        ]);

        if ($this->_encoding !== null) {
            $tag->setAttribute('enctype', $this->_encoding);
        } else {
            switch ($this->_method) {
                case 'post':
                    $tag->setAttribute('enctype', self::ENC_MULTIPART);
                    break;

                case 'get':
                    $tag->setAttribute('enctype', self::ENC_URLENCODED);
                    break;
            }
        }

        if ($this->_name !== null) {
            $tag->setAttribute('name', $this->_name);
        }

        $this->_applyTargetAwareAttributes($tag);
        $this->_applyVisualInputAttributes($tag);

        if ($this->_acceptCharset !== null) {
            $tag->setAttribute('accept-charset', $this->_acceptCharset);
        }

        return $tag->renderWith($children, true);
    }


    // Action
    public function setAction($action)
    {
        $this->_action = $action;
        return $this;
    }

    public function getAction()
    {
        return $this->_action;
    }


    // Method
    public function setMethod($method)
    {
        if ($method === null) {
            $method = 'post';
        }

        $method = strtolower((string)$method);

        if (!in_array($method, ['get', 'post', 'put', 'delete'])) {
            throw Exceptional::InvalidArgument(
                'Invalid form method: ' . $method
            );
        }

        $this->_method = $method;
        return $this;
    }

    public function getMethod()
    {
        return $this->_method;
    }


    // Encoding
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }


    // Name
    public function setName(?string $name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->_name;
    }


    // Accept charset
    public function setAcceptCharset($charset)
    {
        $this->_acceptCharset = $charset;
        return $this;
    }

    public function getAcceptCharset()
    {
        return $this->_acceptCharset;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
                '*action' => $this->_action,
                '*method' => $this->_method,
                '*encoding' => $this->_encoding,
                '*name' => $this->_name,
                '%tag' => $this->getTag()
        ];

        yield 'values' => $this->_children->toArray();
    }
}
