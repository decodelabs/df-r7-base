<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session\perpetuator;

use df;
use df\core;
use df\user;
use df\arch;
use df\link;
use df\axis;

class Cookie implements user\session\IPerpetuator {

    const SESSION_NAME = '_s';
    const REMEMBER_NAME = '_r';
    const JOIN_NAME = '_j';

    protected $_inputId;
    protected $_canRecall = true;

    public function __construct(user\session\IController $controller) {
        $cookies = df\Launchpad::$application->getHttpRequest()->cookies;
        $isRoot = df\Launchpad::$application->getRouter()->isBaseRoot();

        if(!$isRoot && !$cookies->has(self::SESSION_NAME) && !$cookies->has(self::JOIN_NAME)) {
            $this->_joinRoot();
        }

        if($cookies->has(self::JOIN_NAME)) {
            $key = hex2bin($cookies->get(self::JOIN_NAME));
            $this->_inputId = $this->_consumeJoinKey($key);
        }

        if($this->_inputId === null && $cookies->has(self::SESSION_NAME)) {
            try {
                $this->_inputId = hex2bin($cookies->get(self::SESSION_NAME));
            } catch(\Throwable $e) {
                $this->_inputId = null;
            }
        }

        if($cookies->has(self::REMEMBER_NAME) && $cookies->get(self::REMEMBER_NAME) == '') {
            $this->_canRecall = false;
        }
    }

    public function getInputId() {
        return $this->_inputId;
    }

    public function canRecallIdentity() {
        return $this->_canRecall;
    }

    public function perpetuate(user\session\IController $controller, user\session\IDescriptor $descriptor) {
        $outputId = $descriptor->getPublicKeyHex();

        if($outputId != $this->_inputId) {
            $this->_setSessionCookie($outputId);
        }

        return $this;
    }

    protected function _setSessionCookie($outputId) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();
            $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                self::SESSION_NAME, $outputId, null, true
            ));
        }
    }

    public function destroy(user\session\IController $controller) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            // Remove session cookie
            $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
                self::SESSION_NAME, 'deleted', null, true
            ));

            // Set remember cookie to ''
            $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                self::REMEMBER_NAME, '', null, true
            ));
        }

        return $this;
    }

    public function handleDeadPublicKey($publicKey) {
        $cookies = df\Launchpad::$application->getHttpRequest()->cookies;
        $isRoot = df\Launchpad::$application->getRouter()->isBaseRoot();

        if(!$isRoot && !$cookies->has(self::JOIN_NAME)) {
            $this->_joinRoot();
        }
    }

    protected function _joinRoot() {
        $key = $this->_generateJoinKey();
        $this->setJoinKey($key);
        $httpRequest = df\Launchpad::$application->getHttpRequest();
        $router = df\Launchpad::$application->getRouter();
        $request = $router->requestToUrl(arch\Request::factory('account/join-session?key='.bin2hex($key)));
        $request->query->rf = $router->urlToRequest($httpRequest->url)->encode();
        $redirect = new link\http\response\Redirect($request);

        throw new arch\ForcedResponse($redirect);
    }

    public function perpetuateRecallKey(user\session\IController $controller, user\session\RecallKey $key) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                self::REMEMBER_NAME,
                $key->getInterlaceKey(),
                '+1 month',
                true
            ));
        }

        return $this;
    }

    public function getRecallKey(user\session\IController $controller) {
        $httpRequest = df\Launchpad::$application->getHttpRequest();

        if(!$httpRequest->hasCookieData()) {
            return null;
        }

        $value = $httpRequest->cookies->get(self::REMEMBER_NAME);

        if(!empty($value)) {
            return new user\session\RecallKey(
                substr($value, 20, 1),
                substr($value, 0, 20).substr($value, 21)
            );
        }
    }

    public function destroyRecallKey(user\session\IController $controller) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
                self::REMEMBER_NAME, '', null, true
            ));
        }

        return $this;
    }


    public function setJoinKey($key) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                self::JOIN_NAME,
                bin2hex($key),
                '+3 minutes',
                true
            ));
        }

        return $this;
    }

    public function destroyJoinKey() {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
                self::JOIN_NAME, '', null, true
            ));
        }

        return $this;
    }

    protected function _generateJoinKey() {
        return axis\Model::loadUnitFromId('session/stub')->generateKey();
    }

    protected function _consumeJoinKey($key) {
        $output = axis\Model::loadUnitFromId('session/descriptor')->select('publicKey')
            ->whereCorrelation('id', '=', 'sessionId')
                ->from('axis://session/Stub', 'stub')
                ->where('key', '=', $key)
                ->endCorrelation()
            ->toValue('publicKey');

        if($output !== null) {
            axis\Model::loadUnitFromId('session/stub')->delete()
                ->where('key', '=', $key)
                ->execute();

            $this->_setSessionCookie(bin2hex($output));
        }

        $this->destroyJoinKey();
        return $output;
    }
}
