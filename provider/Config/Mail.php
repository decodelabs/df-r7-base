<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Dovetail\Repository;
use DecodeLabs\R7\Config\Http as HttpConfig;
use DecodeLabs\Systemic;
use df\flow\mail\Address;
use df\flow\mail\transport\Base as TransportBase;
use df\link\http\Url;

class Mail implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        return [
            'defaultTransport' => 'Mail',
            'defaultAddress' => static::getDefaultAdminAddress(),
            'defaultReturnPath' => null,
            'adminAddresses' => [],
            'devAddresses' => [],
            'captureInTesting' => true,
            'transports' => TransportBase::getAllDefaultConfigValues(),
            'listSources' => [
                '!example' => [
                    'adapter' => 'Mailchimp3',
                    'apiKey' => null
                ]
            ]
        ];
    }

    public function getDefaultTransport(): string
    {
        return $this->data->defaultTransport->as('string', [
            'default' => 'Mail'
        ]);
    }

    public function getDefaultTransportSettings(): Repository
    {
        return $this->getTransportSettings($this->getDefaultTransport());
    }

    public function getTransportSettings(string $name): Repository
    {
        return $this->data->transports->{$name};
    }




    public function getDefaultAddress(): string
    {
        return $this->data->defaultAddress->as('?string') ??
            static::getDefaultAdminAddress();
    }


    public function getDefaultReturnPath(): ?string
    {
        return $this->data->defaultReturnPath->as('?string');
    }



    /**
     * @return array<Address>
     */
    public function getAdminAddresses(): array
    {
        $output = [];

        foreach ($this->data->adminAddresses as $address) {
            if ($address = Address::factory($address->getValue())) {
                $output[] = $address;
            }
        }

        if (empty($output)) {
            $output[] = Address::factory($this->getDefaultAddress());
        }

        return $output;
    }

    protected static function getDefaultAdminAddress(): string
    {
        $name = Systemic::getCurrentProcess()->getOwnerName();
        $rootUrl = HttpConfig::load()->getRootUrl();

        if ($rootUrl) {
            $domain = (new Url($rootUrl))->getDomain();
        } else {
            $domain = gethostname();
        }

        return $name . '@' . $domain;
    }



    /**
     * @return array<Address>
     */
    public function getDevAddresses(): array
    {
        $output = [];

        foreach ($this->data->devAddresses as $address) {
            if ($address = Address::factory($address->getValue())) {
                $output[] = $address;
            }
        }

        if (empty($output)) {
            $output[] = Address::factory($this->getDefaultAddress());
        }

        return $output;
    }


    // Capture
    public function shouldCaptureInTesting(): bool
    {
        return $this->data->captureInTesting->as('bool', [
            'default' => true
        ]);
    }


    /**
     * @return array<string, Repository>
     */
    public function getListSources(): array
    {
        $output = [];

        foreach ($this->data->listSources as $key => $node) {
            if (substr((string)$key, 0, 1) == '!') {
                continue;
            }

            /** @var Repository $node */
            $output[(string)$key] = $node;
        }

        return $output;
    }

    public function getListSource(string $id): Repository
    {
        return clone $this->data->listSources->{$id};
    }
}
