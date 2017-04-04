<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\mint\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\flex;
use df\mint;
use df\mesh;

class TaskSyncPlans extends arch\node\Task {

    public function execute() {
        mesh\event\Hook::toggleEnabled(false);

        if(!$this->payment->isEnabled()) {
            $this->io->writeError('Payments are currently disabled');
            return;
        }

        $this->io->writeLine('Syncing plans...');
        $this->io->indent();

        $gateway = $this->payment->getSubscriptionGateway();
        $records = $this->data->mint->plan->fetch()
            ->toKeyArray('id');

        $sync = $gateway->syncPlans((function() use($gateway, $records) {
            foreach($records as $id => $record) {
                yield $gateway->newPlan(
                        (string)$record['id'],
                        $record['name'],
                        new mint\Currency($record['amount'], $record['currency']),
                        $record['interval']
                    )
                    ->setIntervalCount($record['intervalCount'])
                    ->setStatementDescriptor($record['statementDescriptor'])
                    ->setTrialDays($record['trialDays']);
            }
        })());

        foreach($sync as $action => $plan) {
            $id = $plan->getId();

            if(!flex\Guid::isValidString($id)) {
                $this->io->writeLine('Skipped: '.$id.' "'.$plan->getName().'"');
                continue;
            }

            if(!isset($records[$id])) {
                $record = $this->data->mint->plan->newRecord([
                    'id' => $id
                ]);
            } else {
                $record = $records[$id];
            }

            $record->import([
                    'name' => $plan->getName(),
                    'amount' => $plan->getAmount()->getAmount(),
                    'currency' => $plan->getAmount()->getCode(),
                    'interval' => $plan->getInterval(),
                    'intervalCount' => $plan->getIntervalCount(),
                    'statementDescriptor' => $plan->getStatementDescriptor(),
                    'trialDays' => $plan->getTrialDays()
                ])
                ->save();

            $this->io->writeLine(ucfirst(rtrim($action, 'e')).'ed: '.$id.' "'.$plan->getName().'"');
        }

        $gateway->clearPlanCache();

        $this->io->outdent();
        mesh\event\Hook::toggleEnabled(true);
    }
}