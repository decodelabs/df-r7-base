<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\amazonSes;

use df;
use df\core;
use df\spur;
use df\flow;
use df\link;
    
class Mediator implements IMediator {

    protected $_accessKey;
    protected $_secretKey;
    protected $_activeUrl;
    protected $_activeRequest;
    protected $_httpClient;

    public function __construct($url=null, $accessKey=null, $secretKey=null) {
        $this->_httpClient = new link\http\Client();

        if($url !== null) {
            $this->setUrl($url);
        }

        if($accessKey !== null) {
            $this->setAccessKey($accessKey);
        }

        if($secretKey !== null) {
            $this->setSecretKey($secretKey);
        }
    }

// Client
    public function getHttpClient() {
        return $this->_httpClient;
    }


// Url
    public function setUrl($url) {
        $this->_activeUrl = link\http\Url::factory($url);
        $this->_activeUrl->isSecure(true);
        return $this;
    }

    public function getUrl() {
        return $this->_activeUrl;
    }

// Keys
    public function setAccessKey($key) {
        $this->_accessKey = $key;
        return $this;
    }

    public function getAccessKey() {
        return $this->_accessKey;
    }

    public function setSecretKey($key) {
        $this->_secretKey = $key;
        return $this;
    }

    public function getSecretKey() {
        return $this->_secretKey;
    }


// API
    public function getVerifiedAddresses() {
        $xml = $this->callServer('get', [
            'Action' => 'ListVerifiedEmailAddresses'
        ]);

        $output = [];

        foreach($xml->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
            $output[] = flow\mail\Address::factory((string)$address);
        }

        return $output;
    }

    public function deleteVerifiedAddress($address) {
        $address = flow\mail\Address::factory($address);
        
        $xml = $this->callServer('delete', [
            'Action' => 'DeleteVerifiedEmailAddress',
            'EmailAddress' => $address->getAddress()
        ]);

        return $this;
    }

    public function getSendQuota() {
        $xml = $this->callServer('get', [
            'Action' => 'GetSendQuota'
        ]);

        return [
            'max24HourSend' => (string)$xml->GetSendQuotaResult->Max24HourSend,
            'maxSendRate' => (string)$xml->GetSendQuotaResult->MaxSendRate,
            'sentLast24Hours' => (string)$xml->GetSendQuotaResult->SentLast24Hours
        ];
    }

    public function getSendStatistics() {
        $xml = $this->callServer('get', [
            'Action' => 'GetSendStatistics'
        ]);

        $dataPoints = [];

        foreach($xml->GetSendStatisticsResult->SendDataPoints->member as $dataPoint) {
            $dataPoints[] = [
                'bounces' => (string)$dataPoint->Bounces,
                'complaints' => (string)$dataPoint->Complaints,
                'deliveryAttempts' => (string)$dataPoint->DeliveryAttempts,
                'rejects' => (string)$dataPoint->Rejects,
                'timestamp' => (string)$dataPoint->Timestamp,
            ];
        }

        return $dataPoints;
    }


    public function sendMessage(flow\mail\IMessage $message) {
        $params = ['Action' => 'SendEmail'];

        $i = 1;
        foreach($message->getToAddresses() as $address) {
            $params['Destination.ToAddresses.member.'.$i] = (string)$address;
            $i++;
        }

        $i = 1;
        foreach($message->getCCAddresses() as $address) {
            $params['Destination.CcAddresses.member.'.$i] = (string)$address;
        }

        $i = 1;
        foreach($message->getBccAddresses() as $address) {
            $params['Destination.BccAddresses.member.'.$i] = (string)$address;
        }

        if($address = $message->getReplyToAddress()) {
            $params['ReplyToAddresses.member.1'] = (string)$address;
        }

        $params['Source'] = (string)$message->getFromAddress();

        if(null !== ($subject = $message->getSubject())) {
            $params['Message.Subject.Data'] = $subject;
        }

        if($bodyText = $message->getBodyText()) {
            $params['Message.Body.Text.Data'] = $bodyText->getContent();
        }

        if($bodyHtml = $message->getBodyHtml()) {
            $params['Message.Body.Html.Data'] = $bodyHtml->getContent();
        }

        $xml = $this->callServer('post', $params);
        return (string)$xml->SendEmailResult->MessageId;
    }

    public function sendRawMessage(flow\mail\IMessage $message) {
        $xml = $this->callServer('post', [
            'Action' => 'SendRawEmail',
            'RawMessage.Data' => (string)$message,
            'Source' => $message->getFromAddress()->getAddress()
        ]);

        return (string)$xml->SendRawEmailResult->MessageId;
    }

    public function sendRawString($from, $string) {
        $from = flow\mail\Address::factory($from);

        $xml = $this->callServer('post', [
            'Action' => 'SendRawEmail',
            'RawMessage.Data' => (string)$string,
            'Source' => $from->getAddress()
        ]);

        return (string)$xml->SendRawEmailResult->MessageId;
    }



// IO
    public function callServer($method, array $data=[]) {
        if(!$this->_activeUrl) {
            throw new RuntimeException(
                'Amazon SES API url has not been set'
            );
        }

        if(!$this->_accessKey) {
            throw new RuntimeException(
                'Amazon SES access key has not been set'
            );
        }

        if(!$this->_secretKey) {
            throw new RuntimeException(
                'Amazon SES secret key has not been set'
            );
        }

        if(!$this->_activeRequest) {
            $this->_activeUrl->shouldEncodeQueryAsRfc3986(true);
            $this->_activeRequest = link\http\request\Base::factory($this->_activeUrl);
        }

        $request = clone $this->_activeRequest;
        $url = $request->getUrl();
        $request->setMethod($method);
        $headers = $request->getHeaders();

        $date = gmdate('D, d M Y H:i:s e');
        $headers->set('Date', $date);

        $auth = 'AWS3-HTTPS AWSAccessKeyId='.$this->_accessKey;
        $auth .= ',Algorithm=HmacSHA256,Signature='.base64_encode(hash_hmac('sha256', $date, $this->_secretKey, true));
        $headers->set('X-Amzn-Authorization', $auth);
        $headers->set('Connection', 'close');

        if(!empty($data)) {
            if($method == 'post') {
                $request->setPostData($data);
                $request->getHeaders()->set('content-type', 'application/x-www-form-urlencoded');
            } else {
                $url->setQuery($data);
            }
        }

        $response = $this->_httpClient->sendRequest($request);
        $xml = simplexml_load_string($response->getContent());

        if($response->getHeaders()->hasErrorStatusCode()) {
            throw new ApiException($xml);
        }

        return $xml;
    }
}