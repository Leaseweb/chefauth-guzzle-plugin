<?php

namespace LeaseWeb\ChefGuzzle\Plugin\ChefAuth;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Common\Event;
use Guzzle\Stream\Stream;


/**
 * Class ChefAuthPlugin
 *
 * @author Nico Di Rocco <n.dirocco@tech.leaseweb.com>
 */
class ChefAuthPlugin implements EventSubscriberInterface
{
    protected $clientName;
    protected $key;
    protected $keyLocation;

    public function __construct($clientName, $keyLocation)
    {
        $this->clientName = $clientName;
        $this->keyLocation = $keyLocation;
    }

    public function getKey()
    {
        if (false === isset($this->key)) {
            $this->key = openssl_pkey_get_private(file_get_contents($this->getKeylocation()));
        }

        return $this->key;
    }

    public function getKeylocation()
    {
        return $this->keyLocation;
    }

    public function setKeylocation($keyLocation)
    {
        $this->keyLocation = $keyLocation;

        unset($this->key);
    }

    public function getClientName()
    {
        return $this->clientName;
    }

    public function setClientname($clientName)
    {
        $this->clientName = $clientName;
    }

    public static function getSubscribedEvents()
    {
        return array('request.before_send' => 'onBeforeSend');
    }

    public function onBeforeSend(Event $event)
    {
        $request = $event['request'];

        if ('Guzzle\Http\Message\EntityEnclosingRequest' === get_class($request)) {
            if (null === $request->getBody()) {
                $request->setBody('{}');
            }
            $hashedBody = $this->chunkedBase64Encode(Stream::getHash($request->getBody(), 'sha1', true));
        } else {
            $hashedBody = $this->sha1AndBase64Encode('');
        }

        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        $request->setHeader('Accept', 'application/json');
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('X-Chef-Version', '0.10.8');
        $request->setHeader('X-Ops-Sign', 'version=1.0');
        $request->setHeader('X-Ops-Timestamp', $timestamp);
        $request->setHeader('X-Ops-Userid', $this->getClientName());
        $request->setHeader('X-Ops-Content-Hash', $hashedBody);

        $signature = $this->signRequest($request->getMethod(), $request->getPath(), $hashedBody, $timestamp);
        foreach (explode('\n', $signature) as $i => $chunk) {
            $n = $i+1;
            $request->setHeader("X-Ops-Authorization-{$n}", $chunk);
        }
    }

    /**
     * Encode a string to base64
     *
     * @param string $value The string to encode to base64
     *
     * @return string
     */
    protected function chunkedBase64Encode($value)
    {
        return rtrim(chunk_split(base64_encode($value), 60, '\n'), '\n');
    }

    protected function sha1AndBase64Encode($value)
    {
        return $this->chunkedBase64Encode(sha1($value, true));
    }

    protected function signRequest($method, $path, $hashedBody, $timestamp)
    {
        $hashedPath = $this->sha1AndBase64Encode($path);

        $canonicalRequest  = "Method:{$method}\n";
        $canonicalRequest .= "Hashed Path:{$hashedPath}\n";
        $canonicalRequest .= "X-Ops-Content-Hash:{$hashedBody}\n";
        $canonicalRequest .= "X-Ops-Timestamp:{$timestamp}\n";
        $canonicalRequest .= "X-Ops-UserId:{$this->getClientName()}";

        openssl_private_encrypt($canonicalRequest, $signature, $this->getKey());

        return $this->chunkedBase64Encode($signature);
    }
}
