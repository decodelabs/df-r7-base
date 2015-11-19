<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\shared\tasks\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

class HttpInvoke extends arch\action\Base {

    const DEFAULT_ACCESS = arch\IAccess::ALL;

    public function executeAsHtml() {
        $view = $this->apex->view('Invoke.html');
        $view['token'] = $this->request['token'];

        return $view;
    }

    public function executeAsStream() {
        return $this->http->generator('text/plain; charset=UTF-8', function($generator) {
            $invoke = $this->data->task->invoke->authorize($this->request['token']);
            $generator->writeChunk(str_repeat(' ', 1024));

            if(!$invoke) {
                $generator->writeChunk('Task invoke token is no longer valid - please try again!');
                return;
            }

            $this->task->launch(
                $invoke['request'],
                new core\io\Multiplexer(['generator' => $generator], 'httpPassthrough'),
                $invoke['environmentMode']
            );
        });
    }
}