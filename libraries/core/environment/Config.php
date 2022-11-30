<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\environment;

use DecodeLabs\Exceptional;

use DecodeLabs\Systemic;
use df\core;

class Config extends core\Config
{
    public const ID = 'environment';
    public const STORE_IN_MEMORY = true;
    public const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues(): array
    {
        return [
            'mode' => 'development',
            'binaryPaths' => [],
            'daemonsEnabled' => false,
            'daemonUser' => $this->_extrapolateDaemonUser(),
            'daemonGroup' => $this->_extrapolateDaemonGroup()
        ];
    }


    // Mode
    public function setMode($mode)
    {
        $this->values->mode = Mode::normalize($mode);
        return $this;
    }

    public function getMode()
    {
        return $this->values->get('mode', 'testing');
    }



    // Vendor binary paths
    public function setBinaryPath($id, $path)
    {
        $this->values->binaryPaths->{$id} = $path;
        return $this;
    }

    public function getBinaryPath($id)
    {
        return $this->values->binaryPaths->get($id, $id);
    }



    // Daemons
    public function canUseDaemons(bool $flag = null)
    {
        if ($flag !== null) {
            $this->values->daemonsEnabled = $flag;
            return $this;
        }

        return (bool)$this->values['daemonsEnabled'];
    }

    public function setDaemonUser($user)
    {
        if (is_numeric($user)) {
            $user = Systemic::$os->userIdToUserName($user);
        }

        if (empty($user)) {
            throw Exceptional::InvalidArgument(
                'Invalid username detected'
            );
        }

        $this->values->daemonUser = $user;
        return $this;
    }

    public function getDaemonUser()
    {
        $output = null;
        $save = false;

        if (!isset($this->values['daemonUser'])) {
            $output = $this->_extrapolateDaemonUser();
            $save = true;
        } else {
            $output = $this->values['daemonUser'];
        }

        if (empty($output)) {
            $output = $this->_extrapolateDaemonUser();
            $save = true;
        }

        if ($save && !empty($output)) {
            $this->setDaemonUser($output);
            $this->save();
        }

        return $output;
    }

    protected function _extrapolateDaemonUser()
    {
        return Systemic::getCurrentProcess()->getOwnerName();
    }

    public function setDaemonGroup($group)
    {
        if (is_numeric($group)) {
            $group = Systemic::$os->groupIdToGroupName($group);
        }

        if (empty($group)) {
            throw Exceptional::InvalidArgument(
                'Invalid group name detected'
            );
        }

        $this->values['daemonGroup'] = $group;
        return $this;
    }

    public function getDaemonGroup()
    {
        $output = null;
        $save = false;

        if (!isset($this->values['daemonGroup'])) {
            $output = $this->_extrapolateDaemonGroup();
            $save = true;
        } else {
            $output = $this->values['daemonGroup'];
        }

        if (empty($output)) {
            $output = $this->_extrapolateDaemonGroup();
            $save = true;
        }

        if ($save && !empty($output)) {
            $this->setDaemonGroup($output);
            $this->save();
        }

        return $output;
    }

    protected function _extrapolateDaemonGroup()
    {
        return Systemic::getCurrentProcess()->getGroupName();
    }
}
