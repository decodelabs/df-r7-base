<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use df\arch;
use df\aura;
use df\core;

class OrderList extends Base implements IMappedListWidget
{
    use TWidget_MappedList;

    public const PRIMARY_TAG = 'ul.order';

    protected $_data;

    public function __construct(arch\IContext $context, $data)
    {
        parent::__construct($context);

        if ($data instanceof core\collection\IPageable) {
            $data = $data->getPaginator();
        }

        if (!$data instanceof core\collection\IOrderablePaginator) {
            $data = null;
        }

        $this->_data = $data;
    }

    protected function _render()
    {
        if (!$this->_data) {
            return '';
        }

        $tag = $this->getTag();
        $orderData = $this->_data->getOrderDirectives();
        $orderFields = $this->_data->getOrderableFieldDirectives();

        if (empty($orderData) || empty($orderFields)) {
            return '';
        } else {
            $keyMap = $this->_data->getKeyMap();
            $request = clone $this->_context->request;
            $query = $request->getQuery();
        }

        if (empty($this->_fields)) {
            $this->_importDefaultFields();
        }

        $content = [
            new aura\html\Element('li.label', $this->_context->_('Order'))
        ];

        foreach ($this->_fields as $fieldKey => $field) {
            foreach ($field->getHeaderList() as $key => $label) {
                if (!isset($orderFields[$key])) {
                    continue;
                }


                $tagContent = [];
                $nullOrder = 'ascending';
                $isNullable = null;

                if (isset($orderData[$key])) {
                    $direction = $orderData[$key]->getReversedDirection();
                    $isNullable = $orderData[$key]->isFieldNullable();
                    $nullOrder = $orderData[$key]->getNullOrder();
                    $isActive = true;
                } else {
                    if (isset($orderFields[$key])) {
                        $direction = $orderFields[$key]->getDirection();
                    } else {
                        $direction = 'ASC';
                    }

                    $isActive = false;
                }

                $query->__set($keyMap['order'], $key . ' ' . $direction);

                $class = 'order ' . strtolower(trim($direction, '!^*')) . ' null-' . $nullOrder;

                if ($isActive) {
                    $class .= ' active';
                }

                $tagContent[] = (new aura\html\Element('a', $label, [
                        'href' => $this->_context->uri->__invoke($request),
                        'class' => $class,
                        'rel' => 'nofollow'
                    ]))
                    ->render();

                if ($isActive && $isNullable !== false) {
                    $direction = trim($direction, '!^*') == 'DESC' ? 'ASC' : 'DESC';

                    switch ($nullOrder) {
                        case 'ascending':
                        case 'descending':
                            $direction .= '!';
                            $newOrder = 'last';
                            break;

                        default:
                            $newOrder = 'ascending';
                            break;
                    }

                    $query->__set($keyMap['order'], $key . ' ' . $direction);

                    $tagContent[] = (new aura\html\Element('a', $newOrder == 'ascending' ? '○' : '●', [ // @ignore-non-ascii
                            'href' => $this->_context->uri->__invoke($request),
                            'class' => 'null-order null-' . $newOrder,
                            'rel' => 'nofollow'
                        ]))
                        ->render();
                }

                $content[] = (new aura\html\Element('li.field-' . $key, $tagContent))->render();
            }
        }

        return $tag->renderWith($content, true);
    }

    protected function _importDefaultFields()
    {
        $fields = $this->_data->getOrderableFields();

        foreach ($fields as $field) {
            $this->addField($field->getName());
        }
    }
}
