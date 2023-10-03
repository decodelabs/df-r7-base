<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mint;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\core;
use df\user;

class CreditCard implements ICreditCard, Dumpable
{
    public const BRANDS = [
        'visa' => '/^4\d{12}(\d{3})?$/',
        'mastercard' => '/^(5[1-5]\d{4}|677189)\d{10}$/',
        'discover' => '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/',
        'amex' => '/^3[47]\d{13}$/',
        'diners_club' => '/^3(0[0-5]|[68]\d)\d{11}$/',
        'jcb' => '/^35(28|29|[3-8]\d)\d{12}$/',
        'switch' => '/^6759\d{12}(\d{2,3})?$/',
        'solo' => '/^6767\d{12}(\d{2,3})?$/',
        'dankort' => '/^5019\d{12}$/',
        'maestro' => '/^(5[06-8]|6\d)\d{10,17}$/',
        'forbrugsforeningen' => '/^600722\d{10}$/',
        'laser' => '/^(6304|6706|6709|6771(?!89))\d{8}(\d{4}|\d{6,7})?$/'
    ];

    protected $_name;
    protected $_number;
    protected $_last4;
    protected $_brand = false;
    protected $_startMonth;
    protected $_startYear;
    protected $_expiryMonth;
    protected $_expiryYear;
    protected $_cvc;
    protected $_issueNumber;
    protected $_billingAddress;

    public static function fromArray(array $data)
    {
        $output = new self();

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'name':
                    $output->setName($value);
                    break;

                case 'number':
                    $output->setNumber($value);
                    break;

                case 'last4':
                    $output->setLast4Digits($value);
                    break;

                case 'start':
                    $output->setStartString($value);
                    break;

                case 'startMonth':
                    $output->setStartMonth($value);
                    break;

                case 'startYear':
                    $output->setStartYear($value);
                    break;

                case 'expiry':
                    $output->setExpiryString($value);
                    break;

                case 'expiryMonth':
                    $output->setExpiryMonth($value);
                    break;

                case 'expiryYear':
                    $output->setExpiryYear($value);
                    break;

                case 'cvc':
                    $output->setCvc($value);
                    break;

                case 'issueNumber':
                    $output->setIssueNumber($value);
                    break;

                case 'billingAddress':
                    if (is_array($value)) {
                        $value = user\PostalAddress::fromArray($value);
                    }

                    if ($value instanceof user\IPostalAddress) {
                        $output->setBillingAddress($value);
                    }

                    break;
            }
        }

        return $output;
    }

    protected function __construct()
    {
    }

    // Name
    public function setName(string $name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->_name;
    }

    // Number
    public static function isValidNumber(string $number): bool
    {
        $str = '';

        foreach (array_reverse(str_split($number)) as $i => $c) {
            $str .= $i % 2 ? (int)$c * 2 : $c;
        }

        return array_sum(str_split($str)) % 10 === 0;
    }

    public function setNumber(string $number)
    {
        $this->_number = $number;

        if ($number !== null) {
            $this->_last4 = null;
        }

        $this->_brand = false;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->_number;
    }

    public function setLast4Digits(string $digits)
    {
        $this->_last4 = $digits;

        if ($digits !== null) {
            $this->_number = null;
        }

        $this->_brand = false;

        return $this;
    }

    public function getLast4Digits(): ?string
    {
        if ($this->_last4) {
            return $this->_last4;
        }

        return substr($this->_number, -4);
    }


    // Brand
    public static function getSupportedBrands(): array
    {
        return array_keys(self::BRANDS);
    }

    public function getBrand(): ?string
    {
        if ($this->_brand === false) {
            foreach (self::BRANDS as $brand => $regex) {
                if (preg_match($regex, (string)$this->_number)) {
                    $this->_brand = $brand;
                    break;
                }
            }

            if ($this->_brand === false) {
                $this->_brand = null;
            }
        }

        return $this->_brand;
    }


    // Start
    public function setStartMonth(?int $month)
    {
        $this->_startMonth = $month;

        if ($month === null) {
            $this->_startYear = null;
        }

        return $this;
    }

    public function getStartMonth(): ?int
    {
        return $this->_startMonth;
    }

    public function setStartYear(?int $year)
    {
        if ($year !== null && $year <= 999) {
            $year += 2000;
        }

        $this->_startYear = $year;

        if ($year === null) {
            $this->_startMonth = null;
        }

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->_startYear;
    }

    public function setStartString(string $start)
    {
        $parts = explode('/', $start);
        $month = array_shift($parts);
        $year = array_shift($parts);

        if ($month !== null) {
            $month = (int)$month;
        }

        if ($year !== null) {
            $year = (int)$year;
        }

        return $this
            ->setStartMonth($month)
            ->setStartYear($year);
    }

    public function getStartString(): ?string
    {
        return $this->_startMonth . '/' . $this->_startYear;
    }

    public function getStartDate(): ?core\time\IDate
    {
        return new core\time\Date($this->_startYear . '-' . $this->_startMonth . '-1');
    }


    // Expiry
    public function setExpiryMonth(int $month)
    {
        $this->_expiryMonth = $month;
        return $this;
    }

    public function getExpiryMonth(): ?int
    {
        return $this->_expiryMonth;
    }

    public function setExpiryYear(int $year)
    {
        if ($year < 100) {
            $year += 2000;
        }

        $this->_expiryYear = $year;
        return $this;
    }

    public function getExpiryYear(): ?int
    {
        return $this->_expiryYear;
    }

    public function setExpiryString(string $expiry)
    {
        $parts = explode('/', $expiry);
        $month = array_shift($parts);
        $year = array_shift($parts);

        /** @phpstan-ignore-next-line */
        if ($month === null || $year === null) {
            throw Exceptional::InvalidArgument(
                'Invalid expiry date string',
                null,
                $expiry
            );
        }

        $month = (int)$month;
        $year = (int)$year;

        return $this
            ->setExpiryMonth($month)
            ->setExpiryYear($year);
    }

    public function getExpiryString(): ?string
    {
        return $this->_expiryMonth . '/' . $this->_expiryYear;
    }

    public function getExpiryDate(): ?core\time\IDate
    {
        return new core\time\Date($this->_expiryYear . '-' . $this->_expiryMonth . '-1');
    }


    // Cvc
    public function setCvc(string $cvc)
    {
        $this->_cvc = $cvc;
        return $this;
    }

    public function getCvc(): ?string
    {
        return $this->_cvc;
    }


    // Issue number
    public function setIssueNumber(?string $number)
    {
        $this->_issueNumber = $number;
        return $this;
    }

    public function getIssueNumber(): ?string
    {
        return $this->_issueNumber;
    }


    // Billing address
    public function setBillingAddress(user\IPostalAddress $address = null)
    {
        $this->_billingAddress = $address;
        return $this;
    }

    public function getBillingAddress(): ?user\IPostalAddress
    {
        return $this->_billingAddress;
    }


    // Valid
    public function isValid(): bool
    {
        if (!$this->_number || !$this->_expiryMonth || !$this->_expiryYear || !$this->_cvc) {
            return false;
        }


        if (!$date = $this->getExpiryDate()) {
            return false;
        }

        $date = clone $date;

        if ($date->modify('+1 month')->modify('-1 day')->isPast()) {
            return false;
        }

        if (!$this->isValidNumber($this->_number)) {
            return false;
        }

        return true;
    }


    // Array
    public function toArray(): array
    {
        return [
            'name' => $this->_name,
            'number' => $this->_number,
            'last4' => $this->_last4,
            'startMonth' => $this->_startMonth,
            'startYear' => $this->_startYear,
            'expiryMonth' => $this->_expiryMonth,
            'expiryYear' => $this->_expiryYear,
            'cvc' => $this->_cvc,
            'issueNumber' => $this->_issueNumber,
            'billingAddress' => $this->_billingAddress ? $this->_billingAddress->toArray() : null
        ];
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:*name' => $this->_name;

        if ($this->_last4) {
            yield 'property:*number' => str_pad($this->_last4, 16, 'x', STR_PAD_LEFT);
        } else {
            yield 'property:*number' => $this->_number;
        }

        if ($this->_number) {
            yield 'property:*brand' => $this->getBrand();
        }

        if ($this->_startMonth && $this->_startYear) {
            yield 'property:*start' => $this->getStartString();
        } else {
            yield 'property:*start' => null;
        }

        if ($this->_expiryMonth && $this->_expiryYear) {
            yield 'property:*expiry' => $this->getExpiryString();
        } else {
            yield 'property:*expiry' => null;
        }

        yield 'property:*cvc' => $this->_cvc;

        if ($this->_issueNumber) {
            yield 'property:*issueNumber' => $this->_issueNumber;
        }

        if ($this->_billingAddress) {
            yield 'property:*billingAddress' => $this->_billingAddress;
        }

        yield 'property:*valid' => $this->isValid();
    }
}
