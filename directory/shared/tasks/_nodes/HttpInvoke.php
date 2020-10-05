<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\shared\tasks\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

use DecodeLabs\Terminus as Cli;
use DecodeLabs\Atlas;

class HttpInvoke extends arch\node\Base
{
    const DEFAULT_ACCESS = arch\IAccess::ALL;

    public function executeAsHtml()
    {
        $view = $this->apex->view('Invoke.html');
        $view['token'] = $this->request['token'];

        return $view;
    }

    public function executeAsStream()
    {
        return $this->http->generator('text/plain; charset=UTF-8', function ($generator) {
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
                    Atlas::newBroker()->addOutputReceiver($generator)
                ),
                null,
                false,
                false
            );
        });
    }
}
