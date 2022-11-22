<?php

// ecs.php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([__DIR__.'/daemons', __DIR__.'/directory', __DIR__.'/libraries', __DIR__.'/provider', __DIR__.'/models']);
    $ecsConfig->sets([SetList::CLEAN_CODE, SetList::PSR_12]);
};
