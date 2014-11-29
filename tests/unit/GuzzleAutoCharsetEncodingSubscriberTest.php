<?php

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\Mock;
use paslandau\ArrayUtility\ArrayUtil;
use paslandau\GuzzleAutoCharsetEncodingSubscriber\GuzzleAutoCharsetEncodingSubscriber;
use paslandau\WebUtility\EncodingConversion\EncodingConverter;
use paslandau\WebUtility\WebUtil;

class GuzzleAutoCharsetEncodingSubscriberTest extends \PHPUnit_Framework_TestCase {

    private $types = [
        "html4" => "text/html",
        "html5" => "text/html",
        "text-xml" => "text/xml",
        "application-xml" => "application/xml",
    ];

    public function getResponseString($bodyEncoding,$encodingInHeader,$encodingInMeta,$type){

        mb_internal_encoding("utf-8");

        $status = "HTTP/1.1 200 OK";
        $content = "Just a little piece of text with some german umlauts like äöüßÄÖÜ and maybe some more UTF-8 characters";
        $headers = [
            "Date: Wed, 26 Nov 2014 22:26:29 GMT",
            "Server: Apache",
            "Content-Language: en",
            "Vary: Accept-Encoding",
            "ctype" => "Content-Type: {$this->types[$type]};"
        ];
        if($encodingInHeader !== null){
            $headers["ctype"] .= " charset={$encodingInHeader}";
        }
        switch ($type) {
            case "html4" : {
                $meta = "";
                if ($encodingInMeta !== null) {
                    $meta = "<meta http-equiv='content-type' content='{$this->types[$type]}; charset={$encodingInMeta}'>";
                }
                $content = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\"><html><head>{$meta}<title>Umlauts everywhere öäüßÖÄÜ</title></head><body>$content</body></html>";
                break;
            }
            case "html5" : {
                $meta = "";
                if ($encodingInMeta !== null) {
                    $meta = "<meta charset='{$encodingInMeta}'>";
                }
                $content = "<!DOCTYPE html><html><head>{$meta}<title>Umlauts everywhere öäüßÖÄÜ</title></head><body>$content</body></html>";
                break;
            }
            case "text-xml" :
            case "application-xml" : {
                $meta = "";
                if ($encodingInMeta !== null) {
                    $meta = " encoding='{$encodingInMeta}'";
                }
                $content = "<?xml version='1.0'{$meta}><foo><bar>$content</bar></foo>";
                break;
            }
        }
        $headers[] = "";

        $response = [
            $status,
        ];
        $response = array_merge($response,$headers);
        $response[] = $content;
        $response = implode("\r\n",$response);
        if($bodyEncoding !== null){
            $response = mb_convert_encoding($response,$bodyEncoding,mb_internal_encoding());
        }
        return $response;
    }


    public function test_convert(){
        $enc = mb_internal_encoding();

        $inputEnc = "iso-8859-1";
        $converters = [
            "none" => new EncodingConverter("$enc",false,false),
            "header" => new EncodingConverter("$enc",true,false),
            "header-meta" => new EncodingConverter("$enc",true,true),
            "meta" => new EncodingConverter("$enc",false,true),
        ];

        $tests = [];
        foreach($this->types as $type => $mime){
            $tests["none-$type"] = [
                "info" => "Request-Type: $type; Settings: Charset info neither in header nor in body",
                "input" => $this->getResponseString($inputEnc,null,null,$type),
                "expected" => [
                    "none" => $this->getResponseString($inputEnc,null,null,$type),
                    "header" => $this->getResponseString($inputEnc,null,null,$type),
                    "header-meta" => $this->getResponseString($inputEnc,null,null,$type),
                    "meta" => $this->getResponseString($inputEnc,null,null,$type),
                ]
            ];
            $tests["header-$type"] = [
                "info" => "Request-Type: $type; Settings: Charset info only in header but not in body",
                "input" => $this->getResponseString($inputEnc,$inputEnc,null,$type),
                "expected" => [
                    "none" => $this->getResponseString($enc,$inputEnc,null,$type),
                    "header" => $this->getResponseString($enc,$enc,null,$type),
                    "header-meta" => $this->getResponseString($enc,$enc,null,$type),
                    "meta" => $this->getResponseString($enc,$inputEnc,null,$type),
                ]
            ];
            $tests["header-meta-$type"] = [
                "info" => "Request-Type: $type; Settings: Charset info in header and in body",
                "input" => $this->getResponseString($inputEnc,$inputEnc,$inputEnc,$type),
                "expected" => [
                    "none" => $this->getResponseString($enc,$inputEnc,$inputEnc,$type),
                    "header" => $this->getResponseString($enc,$enc,$inputEnc,$type),
                    "header-meta" => $this->getResponseString($enc,$enc,$enc,$type),
                    "meta" => $this->getResponseString($enc,$inputEnc,$enc,$type),
                ]
            ];
            $tests["meta-$type"] = [
                "info" => "Request-Type: $type; Settings: Charset info not in header but only in body",
                "input" => $this->getResponseString($inputEnc,null,$inputEnc,$type),
                "expected" => [
                    "none" => $this->getResponseString($enc,null,$inputEnc,$type),
                    "header" => $this->getResponseString($enc,$enc,$inputEnc,$type),
                    "header-meta" => $this->getResponseString($enc,$enc,$enc,$type),
                    "meta" => $this->getResponseString($enc,null,$enc,$type),
                ]
            ];
        }

        $client = new Client();
        foreach($tests as $name => $data) {
            foreach($data["expected"] as $converterType => $expected){
                $request = $client->createRequest("GET","/");
                $mock = new Mock([$data["input"]]);
                $request->getEmitter()->attach($mock);
                $converter = $converters[$converterType];
                $autoCharsetSub = new GuzzleAutoCharsetEncodingSubscriber($converter);
                $request->getEmitter()->attach($autoCharsetSub);
                /** @var ResponseInterface $response */
                $response = $client->send($request);
                $actual = $response->__toString();
                $msg = [
                    "Error at {$data["info"]} for converter type {$converterType}:",
                    "Input\n" . $data["input"] . "\n",
                    "Excpected\n" . $expected . "\n",
                    "Actual\n" . $actual . "\n",
                ];
                $msg = implode("\n", $msg);
//                echo $msg;
                $this->assertEquals($expected,$actual,$msg);
            }
        }
    }
}