<?php

namespace LeaseWeb\ChefGuzzle\Middleware;

use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

class ChefAuthMiddleware
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

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options = []) use ($handler) {
            $hashedBody = $this->chunkedBase64Encode(hash('sha1', $request->getBody()->getContents(), true));
            $timestamp = gmdate("Y-m-d\TH:i:s\Z");
            $signature = $this->signRequest($request->getMethod(), $request->getUri()->getPath(), $hashedBody, $timestamp);

            $request = $request->withHeader('Accept', 'application/json');
            $request = $request->withHeader('Content-Type', 'application/json');
            $request = $request->withHeader('X-Chef-Version', '0.10.8');
            $request = $request->withHeader('X-Ops-Sign', 'version=1.0');
            $request = $request->withHeader('X-Ops-Timestamp', $timestamp);
            $request = $request->withHeader('X-Ops-Userid', $this->getClientName());
            $request = $request->withHeader('X-Ops-Content-Hash', $hashedBody);

            foreach (explode('\n', $signature) as $i => $chunk) {
                $n = $i+1;
                $request = $request->withHeader("X-Ops-Authorization-{$n}", $chunk);
            }

            return $handler($request, $options);
        };
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
