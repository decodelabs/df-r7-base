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
}