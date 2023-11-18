<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\shared\tasks\_nodes;

use DecodeLabs\Harvest;
use DecodeLabs\R7\Legacy;
use df\arch;

class HttpInvoke extends arch\node\Base
{
    public const DEFAULT_ACCESS = arch\IAccess::ALL;

    public function executeAsHtml()
    {
        $view = $this->apex->view('Invoke.html');
        $view['token'] = $this->request['token'];

        return $view;
    }

    public function executeAsStream()
    {
        return Harvest::liveGenerator(function ($stream) {
            $invoke = $this->data->task->invoke->authorize($this->request['token']);

            if (!$invoke) {
                $stream->write('Task invoke token is no longer valid - please try again!');
                return;
            }

            Legacy::taskCommand($invoke['request'])
                ->start(function ($controller) use ($stream) {
                    /** @phpstan-ignore-next-line */
                    while (true) {
                        $stream->write($controller->read());
                        yield null;
                    }
                });

            return null;
        });
    }
}
