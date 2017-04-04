<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\customer;

use df;
use df\core;
use df\apex;
use df\axis;
use df\mint;
use df\flex;

class Unit extends axis\unit\table\Base {

    const ORDERABLE_FIELDS = ['remoteId', 'date'];
    const DEFAULT_ORDER = 'date DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('user', 'One', 'user/client');

        $schema->addUniqueField('remoteId', 'Text', 64);
        $schema->addField('creationDate', 'Timestamp');

        $schema->addField('isDelinquent', 'Boolean');
        $schema->addField('subscriptions', 'OneToMany', 'subscription', 'customer');
    }

    public function prepareClient(mint\ICreditCard $card=null): mint\ICustomer {
        $gateway = $this->_model->getSubscriptionGateway();
        $client = $this->context->user->client;
        $userId = $client->getId();
        $record = $this->fetchByPrimary($userId);
        $customer = null;


        if($record) {
            try {
                $customer = $gateway->fetchCustomer($record['remoteId']);
            } catch(mint\gateway\ENotFound $e) {
                $customer = null;
            }
        }

        if($customer) {
            if($card) {
                $customer
                    ->setCard($card)
                    ->setLocalId($record['#user'])
                    ->setUserId($userId)
                    ->setEmailAddress($client->getEmail());

                $customer = $gateway->updateCustomer($customer);
            }

            $record->isDelinquent = $customer->isDelinquent();
            $record->save();
        } else {
            if(!$record) {
                $record = $this->newRecord([
                    //'id' => $id = flex\Guid::comb(),
                    'user' => $userId,
                    'remoteId' => '',
                    'creationDate' => 'now'
                ]);
            }

            $customer = $gateway->addCustomer(
                $gateway->newCustomer(
                        $client->getEmail(),
                        $client->getId().' : '.$client->getFullName()
                    )
                    ->setLocalId($userId)
                    ->setCard($card)
                    ->setUserId($client->getId())
            );

            $record->remoteId = $customer->getId();
            $record->save();
        }

        return $customer;
    }
}