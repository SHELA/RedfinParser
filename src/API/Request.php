<?php

namespace Shela\RedfinParser\API;

use GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client as GuzzleHttp;

class Request
{
    private $client;
    private $proxy = '';
    private $basic_headers = [
        'Referer'=>'https://google.com/',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp',
        'Accept-Encoding' => 'gzip, deflate, sdch',
        'Cache-Control' => 'max-age=0',
        'Connection' => 'keep-alive'
    ];

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    public function request($url, $method = 'GET', $headers = [], $type = "json")
    {
        $headers = $this->combineHeaders($headers);
        $client = new GuzzleHttp([
            'cookies' => true,
            'verify' => false,
            'allow_redirects' => true
        ]);

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'RF_UNBLOCK_ID' => 'BtUhMmW7',
            'AKA_A2' => 'A',
        ], 'redfin.com');

        try {
            $request_params = [
                'headers' => $headers,
                'verify'  => false,
                'cookies' => $jar,
                'proxy'   => $this->proxy,
            ];
            if($type == "file"){
                $request_params['stream'] = true;
                $request_params['sink'] = STDOUT;
            }
            $response = $client->request($method, $url, $request_params);
            if($type == "json")
                $result = json_decode($this->parseResult($response->getBody()->getContents()),true);
            elseif($type == "file")
                $result = $response->getBody()->getContents();
            else $result = $response->getBody()->getContents();
            return $result;
        } catch (RequestException $e) {
            $response = $this->StatusCodeHandling($e);
            return $response;
        }
    }

    public function exists($file)
    {
        try {
            $this->client->head($file);
            return true;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
    }

    private function parseResult($result="")
    {
        return str_replace(['{}&&'],'',$result);
    }

    public function setProxy($proxy = '')
    {
        $this->proxy = $proxy;
    }

    private function combineHeaders($headers)
    {
        $faker = \Faker\Factory::create();
        $this->basic_headers['User-Agent'] = $faker->chrome;
        return $this->basic_headers + $headers;
    }

    public function StatusCodeHandling($e)
    {
        if ($e->getResponse()->getStatusCode() == '400') {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        } elseif ($e->getResponse()->getStatusCode() == '422') {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        } elseif ($e->getResponse()->getStatusCode() == '500') {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        } elseif ($e->getResponse()->getStatusCode() == '401') {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        } elseif ($e->getResponse()->getStatusCode() == '403') {
            echo $e->getResponse()->getBody(true)->getContents();
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        } else {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        }
    }
}
