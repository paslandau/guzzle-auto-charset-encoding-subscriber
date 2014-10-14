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

$urls = [
    "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso",
    "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&html=html4",
    "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&html=xml",
    "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&html=xmlApp",
];
$client = new Client();
$replaceHeader = true;
$replaceContent = true;
$encodingConverter = new EncodingConverter("utf-8",$replaceHeader,$replaceContent);
$sub = new FixEncodingSubscriber($encodingConverter);
foreach($urls as $url) {
    $req = $client->createRequest("GET", $url);
    $req->getEmitter()->attach($sub);
    $resp = $client->send($req);
    echo "Request to $url:\n";
    echo "Content-Type: ".$resp->getHeader("content-type")."\n";
    echo $resp->getBody()."\n==\n\n";
}
die();