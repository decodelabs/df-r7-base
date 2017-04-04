<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\hooks;

use df;
use df\core;
use df\apex;
use df\mesh;
use df\mint;
use df\spur;

class MintSubscription extends mesh\event\Hook {

    const EVENTS = [
        'axis://user/Client' => [
            'deactivate' => 'deactivate'
        ],
        'mint://stripe/Event' => [
            'customer.subscription.updated' => 'subscriptionUpdate',
            'customer.subscription.deleted' => 'subscriptionDelete',
            'invoice.payment_succeeded' => 'paymentSuccess',
            'invoice.payment_failed' => 'paymentFailure'
        ]
    ];



// Deactivate
    public function onDeactivate($event) {
        $user = $event->getCachedEntity();

        $list = $this->data->mint->subscription->fetch()
            ->where('customer', '=', (string)$user['id'])
            ->beginWhereClause()
                ->where('endDate', '=', null)
                ->orWhere('endDate', '>', 'now')
                ->endClause();

        foreach($list as $sub) {
            $this->data->mint->subscription->cancel($sub);
        }
    }



// Updated subscription
    protected function onSubscriptionUpdate($event) {
        $sub = $event->getCachedEntity()->data->object;

        $record = $this->data->mint->subscription->fetch()
            ->where('remoteId', '=', $sub['id'])
            ->toRow();

        if(!$record) {
            $customer = $this->data->mint->customer->fetch()
                ->where('remoteId', '=', $sub['customer'])
                ->toRow();

            if(!$customer) {
                return;
            }

            $record = $this->data->mint->subscription->newRecord([
                'customer' => $customer['#user'],
                'stripeId' => $sub['id'],
                'periodStart' => $sub['current_period_start'],
                'periodEnd' => $sub['current_period_end']
            ]);
        }

        $record->import([
                'lastUpdateDate' => 'now',
                'cancelDate' => $sub['canceled_at'],
                'atPeriodEnd' => $sub['cancel_at_period_end'],
                'endDate' => $sub['cancel_at_period_end'] ?
                    $sub['current_period_end'] : $sub['ended_at'],
                'trialStart' => $sub['trial_start'],
                'trialEnd' => $sub['trial_end'],
                'plan' => $sub->plan['id']
            ])
            ->save();

        $event['log']['success'] = true;
    }



// Delete subscription
    protected function onSubscriptionDelete($event) {
        $sub = $event->getCachedEntity()->data->object;

        $record = $this->data->mint->subscription->fetch()
            ->where('remoteId', '=', $sub['id'])
            ->toRow();

        if(!$record) {
            return;
        }

        $record->import([
                'lastUpdateDate' => 'now',
                'cancelDate' => $sub['canceled_at'],
                'endDate' => $sub['ended_at'],
                'periodStart' => $sub['current_period_start'],
                'periodEnd' => $sub['current_period_end'],
                'trialStart' => $sub['trial_start'],
                'trialEnd' => $sub['trial_end']
            ])
            ->save();

        $event['log']['success'] = true;

        $this->mesh->emitEvent($record, 'confirmCancel', [
            'event' => $event->getCachedEntity()
        ]);
    }



// Payment succeeded
    protected function onPaymentSuccess($event) {
        $invoice = $event->getCachedEntity()->data->object;

        foreach($invoice->lines->data as $item) {
            if($item['type'] == 'subscription') {
                $subId = $item['id'];
            } else if($item['type'] == 'invoiceitem' && isset($item['subscription'])) {
                $subId = $item['subscription'];
            } else {
                continue;
            }

            $record = $this->data->mint->subscription->fetch()
                ->where('remoteId', '=', $subId)
                ->toRow();

            if(!$record) {
                continue;
            }

            $sub = $this->payment->getSubscriptionGateway()->fetchSubscription($subId);

            $record->import([
                    'lastUpdateDate' => 'now',
                    'periodStart' => $sub->getPeriodStart(),
                    'periodEnd' => $sub->getPeriodEnd(),
                    'trialStart' => $sub->getTrialStart(),
                    'trialEnd' => $sub->getTrialEnd(),
                    'plan' => $sub->getPlanId(),
                    'nextAttemptDate' => null
                ])
                ->save();

            $record['customer']->import([
                'isDelinquent' => false
            ])->save();

            $event['log']['success'] = true;
        }
    }


// Payment failed
    protected function onPaymentFailure($event) {
        $invoice = $event->getCachedEntity()->data->object;
        $record = null;

        foreach($invoice->lines->data as $item) {
            if($item['type'] == 'subscription') {
                $subId = $item['id'];
            } else if($item['type'] == 'invoiceitem' && isset($item['subscription'])) {
                $subId = $item['subscription'];
            } else {
                continue;
            }

            $record = $this->data->mint->subscription->fetch()
                ->where('remoteId', '=', $subId)
                ->toRow();

            if(!$record) {
                continue;
            }

            $record->nextAttemptDate = $invoice['next_payment_attempt'];
            $record->save();

            $record['customer']->import([
                'isDelinquent' => true
            ])->save();

            $event['log']['success'] = true;
        }

        if($record) {
            $this->mesh->emitEvent($record, 'paymentFail', [
                'event' => $event->getCachedEntity()
            ]);
        }
    }
}