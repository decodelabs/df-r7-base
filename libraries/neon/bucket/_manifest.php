<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\bucket;

use df;
use df\core;
use df\neon;

interface IBucket extends core\lang\IAcceptTypeProcessor
{
    public function getName(): string;
    public function getDisplayName(): string;

    public function isUserSpecific();
    public function allowOnePerUser();
}
