<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\shared\tasks\_nodes;

use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Systemic;

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
        return Legacy::$http->generator('text/plain; charset=UTF-8', function ($generator) {
            $generator->headers->set('X-Accel-Buffering', 'no');

            $invoke = $this->data->task->invoke->authorize($this->request['token']);
            $generator->writeBrowserKeepAlive();

            if (!$invoke) {
                $generator->write('Task invoke token is no longer valid - please try again!');
                return;
            }

            $path = Genesis::$hub->getApplicationPath() . '/entry/';
            $path .= Genesis::$environment->getName() . '.php';
            $request = (string)arch\Request::factory($invoke['request']);


            Systemic::scriptCommand([$path, $request])
                ->setWorkingDirectory(Genesis::$hub->getApplicationPath())
                ->start(function ($controller) use ($generator) {
                    /** @phpstan-ignore-next-line */
                    while (true) {
                        $generator->write($controller->read());
                        yield null;
                    }
                });
        });
    }
}
