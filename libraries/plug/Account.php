<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use DecodeLabs\Dictum;
use DecodeLabs\R7\Legacy;
use df\arch;
use df\flex;
use df\opal;

class Account extends arch\Helper
{
    public function generatePassword(): string
    {
        return flex\Generator::random(6, 10);
    }

    public function lookup(string $fullName, ?string $nickName, string $email, ?string &$password, ?bool &$isNew = null): opal\record\IRecord
    {
        if ($password === null) {
            $password = $this->generatePassword();
        }

        $client = $this->data->user->client->fetch()
            ->where('email', '=', strtolower($email))
            ->toRow();

        $outPass = null;

        if (!$client) {
            $isNew = true;

            $client = $this->create(
                $fullName,
                $nickName,
                $email,
                $outPass = $password
            );
        } else {
            $auth = $client->authDomains->fetch()
                ->where('adapter', '=', 'Local')
                ->toRow();

            if ($auth && $auth['password'] == Legacy::hash($password)) {
                $outPass = $password;
            }
        }

        $password = $outPass;
        return $client;
    }

    public function create(string $fullName, ?string $nickName, string $email, string $password): opal\record\IRecord
    {
        if ($nickName === null) {
            $nickName = Dictum::firstName($fullName);
        }

        $client = $this->data->user->client->newRecord([
            'fullName' => $fullName,
            'nickName' => $nickName,
            'email' => strtolower($email),
            'joinDate' => 'now',
            'status' => 3
        ]);

        $client->save();

        $auth = $this->data->user->auth->newRecord([
            'user' => $client,
            'adapter' => 'Local',
            'identity' => $client['email'],
            'password' => Legacy::hash($password),
            'bindDate' => 'now'
        ]);

        $auth->save();
        return $client;
    }
}
