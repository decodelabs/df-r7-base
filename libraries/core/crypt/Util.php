<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\crypt;

use df\core;

abstract class Util implements IUtil {

    const PBKDF2_ALGORITHM = 'sha256';
    const PBKDF2_KEY_LENGTH = 32;

    const ENCRYPTION_ALGORITHM = 'rijndael-256';
    const ENCRYPTION_MODE = 'ctr';
    const ENCRYPTION_IV_SIZE = 32;

    public static function passwordHash($password, $salt, $iterations=1000) {
        $hashLength = strlen(hash(self::PBKDF2_ALGORITHM, null, true));
        $keyBlocks = ceil(self::PBKDF2_KEY_LENGTH / $hashLength);
        $derivedKey = '';

        for($blockId = 1; $blockId <= $keyBlocks; $blockId++) {
            $initialBlock = $block = hash_hmac(self::PBKDF2_ALGORITHM, $salt.pack('N', $blockId), $password, true);

            for($i = 1; $i < $iterations; $i++) {
                $initialBlock ^= ($block = hash_hmac(self::PBKDF2_ALGORITHM, $block, $password, true));
            }

            $derivedKey .= $initialBlock;
        }

        return substr($derivedKey, 0, self::PBKDF2_KEY_LENGTH);
    }


    public static function encrypt($message, $password, $salt) {
        if(!$module = mcrypt_module_open(self::ENCRYPTION_ALGORITHM, '', self::ENCRYPTION_MODE, '')) {
            throw new RuntimeException(
                'Crypt module '.self::ENCRYPTION_ALGORITHM.' could not be loaded'
            );
        }

        $message = serialize($message);
        $iv = mcrypt_create_iv(self::ENCRYPTION_IV_SIZE, \MCRYPT_RAND);
        $key = self::passwordHash($password, $salt);

        if(mcrypt_generic_init($module, $key, $iv) !== 0) {
            throw new RuntimeException(
                'Unable to initialize crypt module'
            );
        }

        $message  = $iv.mcrypt_generic($module, $message);
        $message .= self::passwordHash($message, $key, 1000);

        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        return $message;
    }

    public static function decrypt($message, $password, $salt) {
        if(!$module = mcrypt_module_open(self::ENCRYPTION_ALGORITHM, '', self::ENCRYPTION_MODE, '')) {
            throw new RuntimeException(
                'Crypt module '.self::ENCRYPTION_ALGORITHM.' could not be loaded'
            );
        }

        $iv = substr($message, 0, self::ENCRYPTION_IV_SIZE);
        $mac = substr($message, -self::PBKDF2_KEY_LENGTH);
        $message = substr($message, self::ENCRYPTION_IV_SIZE, -self::PBKDF2_KEY_LENGTH);
        $key = self::passwordHash($password, $salt);
        $compMac = self::passwordHash($iv.$message, $key, 1000);

        if($mac !== $compMac) {
            throw new InvalidArgumentException(
                'Unable to decrypt message, MAC entry does not match'
            );
        }

        if(mcrypt_generic_init($module, $key, $iv) !== 0) {
            throw new RuntimeException(
                'Unable to initialize crypt module'
            );
        }

        $message = mdecrypt_generic($module, $message);
        $message = unserialize($message);

        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        return $message;
    }
}