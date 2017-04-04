<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\subscription;

use df;
use df\core;
use df\apex;
use df\axis;
use df\mint;
use df\opal;

class Unit extends axis\unit\table\Base {

    const ORDERABLE_FIELDS = [
        'remoteId', 'creationDate', 'lastUpdateDate',
        'startDate', 'endDate', 'cancelDate',
        'atPeriodEnd', 'periodStart', 'periodEnd',
        'nextAttemptDate'
    ];
    const DEFAULT_ORDER = 'creationDate DESC';

    const NAME_FIELD = 'remoteId';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addUniqueField('remoteId', 'Text', 32);
        $schema->addField('customer', 'ManyToOne', 'customer', 'subscriptions');
        $schema->addField('plan', 'ManyToOne', 'plan', 'subscriptions');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('lastUpdateDate', 'Date:Time');

        $schema->addField('startDate', 'Date:Time');
        $schema->addField('endDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('cancelDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('atPeriodEnd', 'Boolean');

        $schema->addField('periodStart', 'Date:Time')
            ->isNullable(true);
        $schema->addField('periodEnd', 'Date:Time')
            ->isNullable(true);

        $schema->addField('trialStart', 'Date:Time')
            ->isNullable(true);
        $schema->addField('trialEnd', 'Date:Time')
            ->isNullable(true);

        $schema->addField('nextAttemptDate', 'Date:Time')
            ->isNullable(true);
    }

    public function syncClient(mint\ICustomer $customer, string $planId=null): ?opal\record\IRecord {
        $gateway = $this->_model->getSubscriptionGateway();
        $userId = $this->context->user->client->getId();
        $currentSubscriptions = $gateway->getSubscriptionsFor($customer);
        $planSubscription = null;

        if($planId !== null) {
            // Look for planSubscription
            foreach($currentSubscriptions as $subscription) {
                if($subscription->getPlanId() == $planId) {
                    $planSubscription = $subscription;
                    break;
                }
            }
        }

        if(null === ($customerLocalId = $customer->getLocalId())) {
            $customerLocalId = (string)$this->_model->select('user')
                ->where('remoteId', '=', $customer->getId())
                ->toValue('user');
        }



        if($active = $this->fetchActive($userId)) {
            // We have a record, update it
            $activeSubscription = null;

            foreach($currentSubscriptions as $subscription) {
                if($subscription->getId() == (string)$active['stripeId']) {
                    $activeSubscription = $subscription;
                    break;
                }
            }


            // Can't find active sub
            if(!$activeSubscription) {
                if(!$planId) {
                    // No plan? meh
                    return null;
                }

                if($planSubscription) {
                    // Have unsynced plan subscription
                    $activeSubscription = $planSubscription;
                } else {
                    // Create subscription
                    $activeSubscription = $gateway->subscribeCustomer(
                        $gateway->newSubscription($customer->getId(), $planId)
                    );
                }
            }


            // Plan has changed
            if($planId !== null
            && $activeSubscription->getPlanId() !== $planId) {
                $activeSubscription = $gateway->updateSubscription(
                    $activeSubscription->setPlanId($planId)
                );
            }


            // Ensure all others are cancelled
            $this->update([
                    'cancelDate' => 'now',
                    'endDate' => 'now'
                ])
                ->where('user', '=', $userId)
                ->where('id', '!=', $active['id'])
                ->beginWhereClause()
                    ->where('cancelDate', '=', null)
                    ->orWhere('cancelDate', '>', 'now')
                    ->endClause()
                ->execute();


            // Update record
            return $active->import([
                'remoteId' => $activeSubscription->getId(),
                'customer' => $customerLocalId,
                'plan' => $activeSubscription->getPlanId(),
                'lastUpdateDate' => 'now',
                'startDate' => $activeSubscription->getStartDate(),
                'endDate' => $activeSubscription->getEndDate(),
                'cancelDate' => $activeSubscription->getCancelDate(),
                'periodStart' => $activeSubscription->getPeriodStart(),
                'periodEnd' => $activeSubscription->getPeriodEnd(),
                'trialStart' => $activeSubscription->getTrialStart(),
                'trialEnd' => $activeSubscription->getTrialEnd()
            ])->save();
        } else if($planSubscription !== null) {
            // The customer is already subscribed to the plan, sync local
            return $this->newRecord([
                'user' => $userId,
                'remoteId' => $planSubscription->getId(),
                'customer' => $customerLocalId,
                'plan' => $planId,
                'lastUpdateDate' => 'now',
                'startDate' => $planSubscription->getStartDate(),
                'endDate' => $planSubscription->getEndDate(),
                'cancelDate' => $planSubscription->getCancelDate(),
                'periodStart' => $planSubscription->getPeriodStart(),
                'periodEnd' => $planSubscription->getPeriodEnd(),
                'trialStart' => $planSubscription->getTrialStart(),
                'trialEnd' => $planSubscription->getTrialEnd()
            ])->save();
        } else if($planId !== null) {
            // Not subscribed, no record
            $subscription = $gateway->subscribeCustomer(
                $gateway->newSubscription($customer->getId(), $planId)
            );

            return $this->newRecord([
                'user' => $userId,
                'remoteId' => $subscription->getId(),
                'customer' => $customerLocalId,
                'plan' => $planId,
                'lastUpdateDate' => 'now',
                'startDate' => $subscription->getStartDate(),
                'endDate' => $subscription->getEndDate(),
                'cancelDate' => $subscription->getCancelDate(),
                'periodStart' => $subscription->getPeriodStart(),
                'periodEnd' => $subscription->getPeriodEnd(),
                'trialStart' => $subscription->getTrialStart(),
                'trialEnd' => $subscription->getTrialEnd()
            ])->save();
        }

        return null;
    }

    public function cancel(mint\IGateway $gateway, opal\record\IRecord $subscription, bool $atPeriodEnd=false) {
        if($subscription['endDate'] && $subscription['endDate']->isPast()) {
            return $subscription;
        }

        if(!$subscription['periodEnd']) {
            $atPeriodEnd = false;
        }

        $subscription->cancelDate = 'now';
        $subscription->endDate = $atPeriodEnd ? $subscription['periodEnd'] : 'now';

        if($subscription['remoteId']) {
            try {
                $sub = $gateway->cancelSubscription($subscription['remoteId'], $atPeriodEnd);

                $subscription->import([
                    'cancelDate' => $sub->getCancelDate(),
                    'endDate' => $atPeriodEnd ?
                        $sub->getPeriodEnd() :
                        $sub->getEndDate(),
                    'periodStart' => $sub->getPeriodStart(),
                    'periodEnd' => $sub->getPeriodEnd()
                ]);

                $subscription->save();
            } catch(mint\gateway\EApi $e) {
                core\logException($e);
            }
        }

        $subscription->save();
        return $subscription;
    }

    public function fetchActive(int $userId=null) {
        if($userId === null) {
            $userId = $this->context->user->client->getId();
        }

        return $this->fetch()
            ->where('customer', '=', $userId)
            ->beginWhereClause()
                ->where('endDate', '=', null)
                ->orWhere('endDate', '>', 'now')
                ->endClause()
            ->toRow();
    }
}