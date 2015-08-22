<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\agent;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addUniqueField('body', 'String', 255);
        $schema->addField('isBot', 'Boolean');
    }


    public function logAgent($agent) {
        if(empty($agent)) {
            return null;
        }

        $output = $this->fetch()
            ->where('body', '=', $agent)
            ->toRow();

        if(!$output) {
            $output = $this->newRecord([
                'body' => $agent,
                'isBot' => $this->isBot($agent)
            ])->save();
        }

        return $output;
    }

    public function logCurrent() {
        return $this->logAgent($this->getCurrentString());
    }


    public function getCurrentString() {
        $userAgent = null;

        try {
            $application = df\Launchpad::$application;

            if($application instanceof core\application\Http) {
                $userAgent = $application->getContext()->http->getUserAgent();
            } else if($application instanceof core\application\Task) {
                if(isset($_SERVER['TERM'])) {
                    $userAgent = $_SERVER['TERM'];
                } else if(isset($_SERVER['TERM_PROGRAM'])) {
                    $userAgent = $_SERVER['TERM_PROGRAM'];
                } else if(isset($_SERVER['TERMINAL'])) {
                    $userAgent = $_SERVER['TERMINAL'];
                } else {
                    $userAgent = 'Terminal';
                }
            }
        } catch(\Exception $e) {}

        return $userAgent;
    }

    protected $_botMatch = [
        'AddThis.com',
        'AhrefsBot',
        'Baiduspider',
        'bingbot',
        'CRAZYWEBCRAWLER',
        'crawler',
        'Googlebot',
        'HyperCrawl',
        'LinkedInBot',
        'MJ12bot',
        'msnbot',
        'NerdyBot',
        'Protocol Discovery',
        'RU_Bot',
        'SeznamBot',
        'Slurp',
        'spbot',
        'Twitterbot',
        'XoviBot',
        'Yandex'
    ];

    public function isBot($agent) {
        foreach($this->_botMatch as $match) {
            if(stristr($agent, $match)) {
                return true;
            }
        }

        return false;
    }
}