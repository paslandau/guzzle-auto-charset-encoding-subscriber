<?php
use GuzzleHttp\Client;
use paslandau\GuzzleAutoCharsetEncodingSubscriber\EncodingConverter;
use paslandau\GuzzleAutoCharsetEncodingSubscriber\GuzzleAutoCharsetEncodingSubscriber;

require_once __DIR__ . '/demo-bootstrap.php';

$client = new Client();
$converter = new EncodingConverter("utf-8"); // define desired output encoding
$sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
$url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso"; // request website with iso-8859-1 encoding
$req = $client->createRequest("GET", $url);
$req->getEmitter()->attach($sub);
$resp = $client->send($req);
echo "Content-Type: ".$resp->getHeader("content-type")."\n";
echo $resp->getBody();