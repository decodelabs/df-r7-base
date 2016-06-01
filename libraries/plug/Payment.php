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

    public function validateCard(core\collection\IInputTree $values, array $map=[]) {
        $map = array_merge([
            'name' => 'name',
            'number' => 'number',
            'expiryMonth' => 'expiryMonth',
            'expiryYear' => 'expiryYear',
            'verificationCode' => 'verificationCode'
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

            // Verification
            ->addRequiredField('verificationCode', 'text')
                ->setMinLength(3)
                ->setMaxLength(3)
                ->setPattern('/^[0-9]+$/')

            ->setDataMap($map)
            ->validate($values);

        $values->{$map['number']}->setValue('');
        $values->{$map['verificationCode']}->setValue('');

        if(!$validator->isValid()) {
            return null;
        }

        $creditCard = mint\CreditCard::fromArray($validator->getValues());

        if(!$creditCard->isValid()) {
            $values->number->addError('invalid', $this->_(
                'Card details invalid, please check and try again'
            ));

            return null;
        }

        return $creditCard;
    }
}