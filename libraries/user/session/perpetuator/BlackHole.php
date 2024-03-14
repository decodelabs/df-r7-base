<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session\perpetuator;

use df\user;

class BlackHole implements user\session\IPerpetuator
{
    public function getInputId()
    {
        return null;
    }

    public function canRecallIdentity()
    {
        return true;
    }

    public function perpetuate(
        user\session\IController $controller,
        user\session\Descriptor $descriptor
    ) {
        return $this;
    }

    public function destroy(
        user\session\IController $controller
    ) {
        return $this;
    }

    public function handleDeadPublicKey($publicKey)
    {
    }

    public function perpetuateRecallKey(
        user\session\IController $controller,
        user\session\RecallKey $key
    ) {
        // How's this going to work?
        return $this;
    }

    public function getRecallKey(
        user\session\IController $controller
    ) {
        return null;
    }

    public function destroyRecallKey(
        user\session\IController $controller
    ) {
        // Derp
        return $this;
    }

    public function perpetuateState(user\IClient $client)
    {
        // nothing
    }
}
