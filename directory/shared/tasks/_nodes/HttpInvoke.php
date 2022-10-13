<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\shared\tasks\_nodes;

use df\arch;

use DecodeLabs\Deliverance;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;

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
        return Legacy::$http->generator('text/plain; charset=UTF-8', function ($generator) {
            $generator->headers->set('X-Accel-Buffering', 'no');

            $invoke = $this->data->task->invoke->authorize($this->request['token']);
            $generator->writeBrowserKeepAlive();

            if (!$invoke) {
                $generator->write('Task invoke token is no longer valid - please try again!');
                return;
            }

            $this->task->launch(
                $invoke['request'],
                Cli::newSession(
                    Cli::newRequest([]),
                    Deliverance::newBroker()->addOutputReceiver($generator)
                ),
                null,
                false,
                false
            );
        });
    }
}
