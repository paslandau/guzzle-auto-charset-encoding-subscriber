<?php

use GuzzleHttp\Client;
use paslandau\GuzzleAutoCharsetEncodingSubscriber\GuzzleAutoCharsetEncodingSubscriber;
use paslandau\WebUtility\EncodingConversion\EncodingConverter;

require_once __DIR__ . '/demo-bootstrap.php';

$client = new Client();
$converter = new EncodingConverter("utf-8",false,false); // define desired output encoding and replace nothing
$sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
$converter = new EncodingConverter("utf-8",true,false); // define desired output encoding and replace only headers
$sub1 = new GuzzleAutoCharsetEncodingSubscriber($converter);
$converter = new EncodingConverter("utf-8",false,true); // define desired output encoding and replace only content
$sub2 = new GuzzleAutoCharsetEncodingSubscriber($converter);
$converter = new EncodingConverter("utf-8",true,true); // define desired output encoding and replace headers & content
$sub3 = new GuzzleAutoCharsetEncodingSubscriber($converter);
$url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso";

$tests = [
    "Using unmodified Guzzle request" => null,
    "Using GuzzleAutoCharsetEncodingSubscriber and replace nothing" => $sub,
    "Using GuzzleAutoCharsetEncodingSubscriber and replace headers" => $sub1,
    "Using GuzzleAutoCharsetEncodingSubscriber and replace meta tags" => $sub2,
    "Using GuzzleAutoCharsetEncodingSubscriber and replace headers and meta tags" => $sub3,
];
foreach($tests as $name => $subscriber) {
    $req = $client->createRequest("GET", $url);
    if ($subscriber !== null) {
        $req->getEmitter()->attach($sub);
    }
    $resp = $client->send($req);
    echo "    $name\n";
    echo "    Request to $url:\n";
    echo "    Content-Type: " . $resp->getHeader("content-type") . "\n\n";
    echo $resp->getBody() . "\n\n";
}