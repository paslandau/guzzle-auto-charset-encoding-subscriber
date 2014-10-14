<?php
/**
 * Created by PhpStorm.
 * User: Hirnhamster
 * Date: 14.10.2014
 * Time: 17:11
 */

use GuzzleHttp\Client;
use paslandau\FixEncodingSubscriber\EncodingConverter;
use paslandau\FixEncodingSubscriber\FixEncodingSubscriber;

require_once __DIR__.'/../../../vendor/autoload.php';

$client = new Client();
$converter = new EncodingConverter("utf-8",true,true); // define desired output encoding and replace nothing
$sub = new FixEncodingSubscriber($converter);
$converter = new EncodingConverter("utf-8",true,false); // define desired output encoding and replace only headers
$sub1 = new FixEncodingSubscriber($converter);
$converter = new EncodingConverter("utf-8",false,true); // define desired output encoding and replace only content
$sub2 = new FixEncodingSubscriber($converter);
$converter = new EncodingConverter("utf-8",true,true); // define desired output encoding and replace headers & content
$sub3 = new FixEncodingSubscriber($converter);
$url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso";

$tests = [
    "Using unmodified Guzzle request" => null,
    "Using FixEncodingSubscriber and replace nothing" => $sub,
    "Using FixEncodingSubscriber and replace headers" => $sub1,
    "Using FixEncodingSubscriber and replace meta tags" => $sub2,
    "Using FixEncodingSubscriber and replace headers and meta tags" => $sub3,
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