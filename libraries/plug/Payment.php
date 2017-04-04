<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\mint;
use df\spur;

class Payment implements core\ISharedHelper {

    use core\TSharedHelper;

    public function isValidCardNumber($number): bool {
        return mint\CreditCard::isValidNumber($number);
    }

    public function validateCard(core\collection\IInputTree $values, array $map=[])/*: mint\ICreditCard*/ {
        $map = array_merge([
            'name' => 'name',
            'number' => 'number',
            'expiryMonth' => 'expiryMonth',
            'expiryYear' => 'expiryYear',
            'cvc' => 'cvc'
        ], $map);

        $validator = $this->context->data->newValidator()

            // Name
            ->addRequiredField('name', 'text')

            // Number
            ->addRequiredField('number', 'text')
                ->setSanitizer(function($value) {
                    return preg_replace('/[^0-9]/', '', $value);
                })
                ->setCustomValidator(function($node, $value) {
                    if(!$this->isValidCardNumber($value)) {
                        $node->addError('invalid', $this->context->_(
                            'Please enter a valid credit card number'
                        ));
                    }
                })

            // Start month
            ->addField('startMonth', 'integer')
                ->setRange(1, 12)

            // Start year
            ->addField('startYear', 'integer')
                ->setRange(1, 12)

            // Expiry month
            ->addRequiredField('expiryMonth', 'integer')
                ->setRange(1, 12)

            // Expiry year
            ->addRequiredField('expiryYear', 'integer')
                ->setRange($min = date('Y'), $min + 10)

            // CVC
            ->addRequiredField('cvc', 'text')
                ->setMinLength(3)
                ->setMaxLength(4)
                ->setPattern('/^[0-9]+$/')

            ->setDataMap($map)
            ->validate($values);

        $values->{$map['number']}->setValue('');
        $values->{$map['cvc']}->setValue('');

        if(!$validator->isValid()) {
            return null;
        }

        $creditCard = $this->newCard($validator->getValues());

        if(!$creditCard->isValid()) {
            $values->number->addError('invalid', $this->context->_(
                'Card details invalid, please check and try again'
            ));

            return null;
        }

        return $creditCard;
    }

    public function currency($amount, $code=null): mint\ICurrency {
        return mint\Currency::factory($amount, $code);
    }

    public function newCard(array $values): mint\ICreditCard {
        return mint\CreditCard::fromArray($values);
    }

    public function newGateway(string $name, $settings=null): mint\IGateway {
        return mint\gateway\Base::factory($name, $settings);
    }



// Model
    public function isEnabled(): bool {
        return $this->context->data->mint->isEnabled();
    }

    public function getPrimaryGateway(): ?mint\IGateway {
        return $this->context->data->mint->getPrimaryGateway();
    }

    public function getSubscriptionGateway(): ?mint\IGateway {
        return $this->context->data->mint->getSubscriptionGateway();
    }

    public function getAccountGateway(string $account): ?mint\IGateway {
        return $this->context->data->mint->getGateway($account);
    }
}