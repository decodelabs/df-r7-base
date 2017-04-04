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

class MintPlan extends mesh\event\Hook {

    const EVENTS = [
        'axis://mint/Plan' => [
            'insert' => 'insert',
            'preUpdate' => 'update',
            'preDelete' => 'delete'
        ],
        'mint://stripe/Event' => [
            'plan.created' => 'remoteChange',
            'plan.updated' => 'remoteChange',
            'plan.deleted' => 'remoteDelete'
        ]
    ];

    public function onInsert($event) {
        $record = $event->getCachedEntity();
        $gateway = $this->payment->getSubscriptionGateway();

        $gateway->addPlan($this->_recordToPlan($gateway, $record));
    }

    public function onUpdate($event) {
        $gateway = $this->payment->getSubscriptionGateway();
        $record = $event->getCachedEntity();
        $plan = $this->_recordToPlan($gateway, $record);

        if($record->hasChanged('name', 'statementDescriptor', 'trialDays')) {
            try {
                $gateway->updatePlan($plan);
            } catch(mint\gateway\ENotFound $e) {
                $gateway->updatePlan($plan);
            }
        }
    }

    protected function _recordToPlan($gateway, $record) {
        return $gateway->newPlan(
                (string)$record['id'],
                $record['name'],
                new mint\Currency($record['amount'], $record['currency']),
                $record['interval']
            )
            ->setIntervalCount($record['intervalCount'])
            ->setTrialDays($record['trialDays'])
            ->setStatementDescriptor($record['statementDescriptor']);
    }

    public function onDelete($event) {
        $gateway = $this->payment->getSubscriptionGateway();
        $record = $event->getCachedEntity();

        try {
            $gateway->deletePlan((string)$record['id']);
        } catch(mint\gateway\EPlan $e) {
            core\logException($e);
        }
    }



// Remote
    public function onRemoteChange($event) {
        $data = $event->getCachedEntity()->data->object;
        $id = $data['id'];

        $record = $this->data->fetchOrCreateForAction(
            'axis://mint/Plan',
            $id,
            function($record) use($id) {
                $record->import([
                    'id' => $id,
                    'weight' => $this->data->mint->plan->select('MAX(weight) as weight')
                        ->toValue('weight') + 1,
                    'isActive' => true
                ]);
            }
        );

        $amount = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);
        mesh\event\Hook::toggleEnabled(false);

        $record->import([
                'name' => $data['name'],
                'amount' => $amount->getAmount(),
                'currency' => $amount->getCode(),
                'interval' => $data['interval'],
                'intervalCount' => $data['interval_count'],
                'statementDescriptor' => $data['statement_descriptor'],
                'trialDays' => $data['trial_period_days']
            ])
            ->save();

        mesh\event\Hook::toggleEnabled(true);
        $event['log']['success'] = true;
    }

    public function onRemoteDelete($event) {
        $data = $event->getCachedEntity()->data->object;
        mesh\event\Hook::toggleEnabled(false);

        $this->data->mint->plan->delete()
            ->where('id', '=', $data['id'])
            ->execute();

        mesh\event\Hook::toggleEnabled(true);
        $event['log']['success'] = true;
    }
}