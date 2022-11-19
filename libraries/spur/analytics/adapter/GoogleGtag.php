<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics\adapter;

use df\aura;
use df\core;
use df\spur;

class GoogleGtag extends Base
{
    protected $_options = [
        'trackingIds' => []
    ];

    public function apply(spur\analytics\IHandler $handler, aura\view\IHtmlView $view)
    {
        $ids = $this->_normalizeTrackingIds($this->getOption('trackingIds'));
        $accounts = [];
        $mainId = null;

        foreach ($ids as $id => $options) {
            $consent = $options['consent'] ?? 'statistics';
            unset($options->consent);

            if (!$view->consent->has($consent)) {
                continue;
            }

            if (!$mainId) {
                $mainId = $id;
            }

            $accounts[$id] = $options->toArray();
        }

        if (!$mainId || empty($accounts)) {
            return;
        }

        $attributes = $handler->getDefinedUserAttributes(
            array_merge($this->getDefaultUserAttributes(), ['id']),
            false
        );

        $userId = $attributes['id'];

        // Initialization
        if (null === ($gtag = $view->getHeadScript('gtag'))) {
            $view->linkJs('https://www.googletagmanager.com/gtag/js?id=' . $mainId, 9999, ['async' => true]);
            $gtag =
                'window.dataLayer = window.dataLayer || [];' . "\n" .
                'function gtag(){dataLayer.push(arguments);}' . "\n" .
                'gtag(\'js\', new Date());' . "\n";
        }

        // Accounts
        foreach ($accounts as $id => $account) {
            if ($userId !== null && !isset($account['user_id'])) {
                $account['user_id'] = $userId;
            }

            $gtag .= 'gtag(\'config\', \'' . $id . '\', ' . json_encode($account) . ');' . "\n";
        }


        // Events
        foreach ($handler->getEvents() as $event) {
            $gtag .= 'gtag(\'event\', \'' . $event->getName() . '\', ' . json_encode([
                'event_category' => $event->getCategory(),
                'event_label' => $event->getLabel()
            ]) . ');' . "\n";
        }


        // ECommerce
        $transactions = $handler->getECommerceTransactions();

        foreach ($transactions as $transaction) {
            $transactionData = [
                'transaction_id' => $transaction->getId(),
                'value' => $transaction->getAmount()->getAmount(),
                'currency' => $transaction->getAmount()->getCode()
            ];

            if ($affiliation = $transaction->getAffiliation()) {
                $transactionData['affiliation'] = $affiliation;
            }

            if ($shipping = $transaction->getShippingAmount()) {
                $transactionData['shipping'] = $shipping->getAmount();
            }

            if ($tax = $transaction->getTaxAmount()) {
                $transactionData['tax'] = $tax->getAmount();
            }

            $gtag .= 'gtag(\'event\', \'purchase\', ' . json_encode($transactionData) . ');' . "\n";
        }

        $view->addHeadScript('gtag', rtrim($gtag));
    }

    protected function _normalizeTrackingIds($ids)
    {
        $values = new core\collection\Tree($ids);
        $tracking = [];

        foreach ($values as $key => $node) {
            if ($node->hasValue()) {
                $key = (string)$node->getValue();
                $tracking[$key] = [];
            } elseif (is_string($key)) {
                $tracking[$key] = $node->toArray();
            } elseif (isset($node->id) && $node instanceof core\collection\Tree) {
                $key = $node['id'];
                unset($node->id);
                $tracking[$key] = $node->toArray();
            }

            if (!isset($tracking[$key]['consent'])) {
                $tracking[$key]['consent'] = 'statistics';
            }

            if (!isset($tracking[$key]['anonymize_ip'])) {
                $tracking[$key]['anonymize_ip'] = true;
            }
        }

        return new core\collection\Tree($tracking);
    }
}
