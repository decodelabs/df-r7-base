<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\mint as mintLib;
use df\spur;

class Mint implements core\ISharedHelper
{
    use core\TSharedHelper;

    public function isValidCardNumber($number): bool
    {
        return mintLib\CreditCard::isValidNumber($number);
    }

    public function validateCard(core\collection\IInputTree $values, array $map=[])/*: mintLib\ICreditCard*/
    {
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
                ->setSanitizer(function ($value) {
                    return preg_replace('/[^0-9]/', '', $value);
                })
                ->extend(function ($value, $field) {
                    if (!$this->isValidCardNumber($value)) {
                        $field->addError('invalid', $this->context->_(
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

        if (!$validator->isValid()) {
            return null;
        }

        $creditCard = $this->newCard($validator->getValues());

        if (!$creditCard->isValid()) {
            $values->number->addError('invalid', $this->context->_(
                'Card details invalid, please check and try again'
            ));

            return null;
        }

        return $creditCard;
    }

    public function currency($amount, $code=null): mintLib\ICurrency
    {
        return mintLib\Currency::factory($amount, $code);
    }

    public function newCard(array $values): mintLib\ICreditCard
    {
        return mintLib\CreditCard::fromArray($values);
    }

    public function newGateway(string $name, $settings=null): mintLib\IGateway
    {
        return mintLib\gateway\Base::factory($name, $settings);
    }



    // Model
    public function isEnabled(): bool
    {
        return $this->context->data->mint->isEnabled();
    }

    public function getPrimaryGateway(): ?mintLib\IGateway
    {
        return $this->context->data->mint->getPrimaryGateway();
    }

    public function getSubscriptionGateway(): ?mintLib\IGateway
    {
        return $this->context->data->mint->getSubscriptionGateway();
    }

    public function getAccountGateway(string $account): ?mintLib\IGateway
    {
        return $this->context->data->mint->getGateway($account);
    }
}
