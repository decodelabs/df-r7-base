<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\crypt;

use df\core;

abstract class Util
{
    public const PBKDF2_ALGORITHM = 'sha256';
    public const PBKDF2_KEY_LENGTH = 32;

    public static function passwordHash(
        $password,
        $salt,
        $iterations=1000
    ) {
        $hashLength = strlen(hash(self::PBKDF2_ALGORITHM, '', true));
        $keyBlocks = ceil(self::PBKDF2_KEY_LENGTH / $hashLength);
        $derivedKey = '';

        for ($blockId = 1; $blockId <= $keyBlocks; $blockId++) {
            $initialBlock = $block = hash_hmac(self::PBKDF2_ALGORITHM, $salt.pack('N', $blockId), $password, true);

            for ($i = 1; $i < $iterations; $i++) {
                $initialBlock ^= ($block = hash_hmac(self::PBKDF2_ALGORITHM, $block, $password, true));
            }

            $derivedKey .= $initialBlock;
        }

        return substr($derivedKey, 0, self::PBKDF2_KEY_LENGTH);
    }
}
