<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\amazonSes;

use DecodeLabs\Exceptional;
use df\flow;
use df\link;

use df\spur;
use Psr\Http\Message\ResponseInterface;

class Mediator implements IMediator
{
    use spur\TGuzzleMediator;

    public const ALGORITHM = 'AWS4-HMAC-SHA256';

    protected $_accessKey;
    protected $_secretKey;

    protected $_region = 'us-east-1';
    protected $_service = 'email';
    protected $_domain = 'amazonaws.com';

    protected $_date;
    protected $_amzDate;

    public function __construct($url = null, $accessKey = null, $secretKey = null)
    {
        if ($url !== null) {
            $this->setUrl($url);
        }

        if ($accessKey !== null) {
            $this->setAccessKey($accessKey);
        }

        if ($secretKey !== null) {
            $this->setSecretKey($secretKey);
        }
    }


    // Url
    public function setUrl($url)
    {
        $domain = explode('.', link\http\Url::factory($url)->getDomain());
        $this->_service = array_shift($domain);
        $this->_region = array_shift($domain);
        $this->_domain = implode('.', $domain);

        return $this;
    }

    public function getUrl()
    {
        return link\http\Url::factory('https://' . $this->getHost());
    }

    public function getHost(): string
    {
        return $this->_service . '.' . $this->_region . '.' . $this->_domain;
    }

    // Keys
    public function setAccessKey($key)
    {
        $this->_accessKey = $key;
        return $this;
    }

    public function getAccessKey()
    {
        return $this->_accessKey;
    }

    public function setSecretKey($key)
    {
        $this->_secretKey = $key;
        return $this;
    }

    public function getSecretKey()
    {
        return $this->_secretKey;
    }


    // API
    public function getVerifiedAddresses()
    {
        $xml = $this->requestXml('get', [
            'Action' => 'ListVerifiedEmailAddresses'
        ]);

        $output = [];

        foreach ($xml->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
            $output[] = flow\mail\Address::factory((string)$address);
        }

        return $output;
    }

    public function deleteVerifiedAddress($address)
    {
        if (!$address = flow\mail\Address::factory($address)) {
            throw Exceptional::UnexpectedValue(
                'Invalid verified address'
            );
        }

        $xml = $this->requestXml('delete', [
            'Action' => 'DeleteVerifiedEmailAddress',
            'EmailAddress' => $address->getAddress()
        ]);

        return $this;
    }

    public function getSendQuota()
    {
        $xml = $this->requestXml('get', [
            'Action' => 'GetSendQuota'
        ]);

        return [
            'max24HourSend' => (string)$xml->GetSendQuotaResult->Max24HourSend,
            'maxSendRate' => (string)$xml->GetSendQuotaResult->MaxSendRate,
            'sentLast24Hours' => (string)$xml->GetSendQuotaResult->SentLast24Hours
        ];
    }

    public function getSendStatistics()
    {
        $xml = $this->requestXml('get', [
            'Action' => 'GetSendStatistics'
        ]);

        $dataPoints = [];

        foreach ($xml->GetSendStatisticsResult->SendDataPoints->member as $dataPoint) {
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



    public function sendMessage(flow\mail\IMessage $message, flow\mime\IMultiPart $mime)
    {
        $params = ['Action' => 'SendEmail'];

        $i = 1;
        foreach ($message->getToAddresses() as $address) {
            $params['Destination.ToAddresses.member.' . $i] = (string)$address;
            $i++;
        }

        $i = 1;
        foreach ($message->getCcAddresses() as $address) {
            $params['Destination.CcAddresses.member.' . $i] = (string)$address;
        }

        $i = 1;
        foreach ($message->getBccAddresses() as $address) {
            $params['Destination.BccAddresses.member.' . $i] = (string)$address;
        }

        if ($address = $message->getReplyToAddress()) {
            $params['ReplyToAddresses.member.1'] = (string)$address;
        }

        $params['Source'] = (string)$message->getFromAddress();

        if (null !== ($subject = $message->getSubject())) {
            $params['Message.Subject.Data'] = $subject;
        }

        if ($bodyText = $message->getBodyText()) {
            $params['Message.Body.Text.Data'] = $bodyText;
        }

        if ($bodyHtml = $message->getBodyHtml()) {
            $params['Message.Body.Html.Data'] = $bodyHtml;
        }

        $xml = $this->requestXml('post', $params);
        return (string)$xml->SendEmailResult->MessageId;
    }

    public function sendRawMessage(flow\mail\IMessage $message, flow\mime\IMultiPart $mime)
    {
        $xml = $this->requestXml('post', [
            'Action' => 'SendRawEmail',
            'RawMessage.Data' => base64_encode((string)$mime),
            'Source' => $message->getFromAddress()->getAddress()
        ]);

        return (string)$xml->SendRawEmailResult->MessageId;
    }


    public function sendRawString($from, $string)
    {
        if (!$from = flow\mail\Address::factory($from)) {
            throw Exceptional::UnexpectedValue(
                'Invalid from address'
            );
        }

        $xml = $this->requestXml('post', [
            'Action' => 'SendRawEmail',
            'RawMessage.Data' => base64_encode((string)$string),
            'Source' => $from->getAddress()
        ]);

        return (string)$xml->SendRawEmailResult->MessageId;
    }



    // IO
    public function requestXml($method, array $data = [], array $headers = [])
    {
        $response = $this->sendRequest($this->createRequest(
            $method,
            '',
            $data,
            $headers
        ));

        return simplexml_load_string((string)$response->getBody());
    }

    public function createUrl(string $path): link\http\IUrl
    {
        $output = $this->getUrl();
        $output->setPath($path);
        return $output;
    }

    protected function _prepareRequest(link\http\IRequest $request): link\http\IRequest
    {
        if (!$this->_accessKey) {
            throw Exceptional::Setup(
                'Amazon SES access key has not been set'
            );
        }

        if (!$this->_secretKey) {
            throw Exceptional::Setup(
                'Amazon SES secret key has not been set'
            );
        }

        $this->_date = gmdate('Ymd');
        $this->_amzDate = gmdate('Ymd\THis\Z');
        $request->prepareHeaders();

        //$params = $request->post->toArray();
        //ksort($params);
        //$canonicalParameters = http_build_query($params, '', '&', PHP_QUERY_RFC3986);


        $headers = $request->getHeaders();
        $canonicalHeaders =
            'content-type:' . $headers->get('content-type') . "\n" .
            'host:' . $this->getHost() . "\n" .
            'x-amz-date:' . $this->_amzDate . "\n";

        $signedHeaders = 'content-type;host;x-amz-date';
        $hash = hash('sha256', $request->getBodyDataString());

        // Task 1
        $canonicalRequest =
            strtoupper($request->getMethod()) . "\n" .
            '/' . "\n" .
            //$canonicalParameters."\n".
            '' . "\n" .
            $canonicalHeaders . "\n" .
            $signedHeaders . "\n" .
            $hash;


        // Task 2
        $scope = $this->_date . '/' . $this->_region . '/email/aws4_request';
        $signatureString =
            self::ALGORITHM . "\n" .
            $this->_amzDate . "\n" .
            $scope . "\n" .
            hash('sha256', $canonicalRequest);

        // Task 3
        $dateHmac = hash_hmac('sha256', $this->_date, 'AWS4' . $this->_secretKey, true);
        $regionHmac = hash_hmac('sha256', $this->_region, $dateHmac, true);
        $serviceHmac = hash_hmac('sha256', 'email', $regionHmac, true);
        $signatureKey = hash_hmac('sha256', 'aws4_request', $serviceHmac, true);
        $signature = hash_hmac('sha256', $signatureString, $signatureKey);

        $auth = self::ALGORITHM . ' Credential=' . $this->_accessKey . '/' . $scope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;
        $headers->set('Authorization', $auth);
        $headers->set('x-amz-date', $this->_amzDate);
        //$headers->set('Connection', 'close');

        return $request;
    }

    protected function _extractResponseError(ResponseInterface $response)
    {
        return Exceptional::Api([
            'message' => 'SES api error',
            'data' => (string)$response->getBody()
        ]);
    }
}
