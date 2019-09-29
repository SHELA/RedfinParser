<?php

namespace Shela\RedfinParser\API;

use GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client as GuzzleHttp;
class Request
{
    private $client;
    private $url;
    private $jar;
    private $proxy = '';
    private $captchakey = '';
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
        $this->jar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'RF_UNBLOCK_ID' => 'BtUhMmW7',
            'AKA_A2' => 'A',
        ], 'redfin.com');
    }

    public function request($url, $method = 'GET', $headers = [], $type = "json", $form_params = false)
    {
        $this->url = $url;
        $headers = $this->combineHeaders($headers);

        $client = new GuzzleHttp([
            'cookies' => true,
            'verify' => false,
            'allow_redirects' => true
        ]);

        try {
            $request_params = [
                'headers' => $headers,
                'verify'  => false,
                'cookies' => $this->jar,
                'proxy'   => $this->proxy,
            ];
            if($type == "file"){
                $request_params['stream'] = true;
                $request_params['sink'] = STDOUT;
            }
            if($form_params){
                $request_params['form_params'] = $form_params;
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

    public function setCaptchakey($captchakey = '')
    {
        $this->captchakey = $captchakey;
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
            if(strpos($e->getResponse()->getBody(true)->getContents(), 'g-recaptcha') !== false){
                $recaptcha = false;
                while (!$recaptcha){
                    $recaptcha = $this->captcha('6LeXKQ4TAAAAAF9-W9Ib3-GqbQdvKVg9xaXsSZD_', $this->url);
                }
                return $this->request($this->url, 'POST', [], false, ['email'=>'', 'g-recaptcha-response' => $recaptcha, 'submit'=>'Submit']);
            }else{
                $response = json_decode($e->getResponse()->getBody(true)->getContents());
            }
            return $response;
        } else {
            $response = json_decode($e->getResponse()->getBody(true)->getContents());

            return $response;
        }
    }

    public function captcha($googleKey = "", $page = "")
    {
        $rtimeout = 5;
        $mtimeout = 120;
        $apiKey = $this->captchakey;
        $retrieve= file_get_contents("http://2captcha.com/in.php?key=".$apiKey."&method=userrecaptcha&googlekey=".$googleKey."&invisible=1&pageurl=".$page);
        $first = array($retrieve);
        $result = explode('OK|', $first[0]);
        $captcha_id = $result[1];
        $waittime = 0;
        sleep($rtimeout);
        while (true) {
            $result = file_get_contents("http://2captcha.com/res.php?key=".$apiKey.'&action=get&id='.$captcha_id);
            if (strpos($result, 'ERROR')!==false) {
                return false;
            }
            if ($result=="CAPCHA_NOT_READY") {
                $waittime += $rtimeout;
                if ($waittime>$mtimeout) {
                    break;
                }
                sleep($rtimeout);
            } else {
                $ex = explode('|', $result);
                if (trim($ex[0])=='OK') {
                    return trim($ex[1]);
                }
            }
        }
        return false;
    }
}
