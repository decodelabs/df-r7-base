<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\error\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\link;

use DecodeLabs\Glitch;
use DecodeLabs\Exceptional;

class TaskDefault extends arch\node\Base
{
    public const CHECK_ACCESS = false;
    public const DEFAULT_ACCESS = arch\IAccess::ALL;

    public function execute()
    {
        if (!$exception = $this->runner->getDispatchException()) {
            throw Exceptional::Forbidden([
                'message' => 'You shouldn\'t be here',
                'http' => 403
            ]);
        }

        $code = $exception->getCode();

        if (
            !link\http\response\HeaderCollection::isValidStatusCode($code) ||
            !link\http\response\HeaderCollection::isErrorStatusCode($code)
        ) {
            $code = 500;
        }

        try {
            $command = implode(' ', array_slice($_SERVER['argv'], 1));
            $this->logs->logException($exception, $command);
        } catch (\Throwable $e) {
            try {
                $this->logs->logException($e);
            } catch (\Throwable $f) {
                Glitch::dumpDie($e, $f, $exception);
            }
        }

        Glitch::dumpException($exception);
    }
}
