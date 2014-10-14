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
$converter = new EncodingConverter("utf-8"); // define desired output encoding
$sub = new FixEncodingSubscriber($converter);
$url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso"; // request website with iso-8859-1 encoding
$req = $client->createRequest("GET", $url);
$req->getEmitter()->attach($sub);
$resp = $client->send($req);
echo "Content-Type: ".$resp->getHeader("content-type")."\n";
echo $resp->getBody();