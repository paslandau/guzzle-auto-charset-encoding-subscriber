<?php

use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use paslandau\GuzzleAutoCharsetEncodingSubscriber\GuzzleAutoCharsetEncodingSubscriber;
use paslandau\WebUtility\EncodingConversion\EncodingConverter;

require_once __DIR__ . '/demo-bootstrap.php';

$urls = [
    "iso-8859-1-html5" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso",
    "iso-8859-1-html4" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&html=html4",
    "iso-8859-1-text-xml" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&html=xml",
    "iso-8859-1-application-xml" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&html=xmlApp",
    "utf8-html5-header-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8",
    "utf8-html4-header-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=html4",
    "utf8-text-xml-header-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=xml",
    "utf8-application-xml-header-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=xmlApp",
    "utf8-html5-header" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8",
    "utf8-html4-header" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=html4",
    "utf8-text-xml-header" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=xml",
    "utf8-application-xml-header" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=xmlApp",
    "utf8-html5-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8",
    "utf8-html4-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=html4",
    "utf8-text-xml-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=xml",
    "utf8-application-xml-meta" => "http://www.myseosolution.de/scripts/encoding-test.php?enc=utf8&html=xmlApp",
];
$client = new Client();
$replaceHeader = true;
$replaceContent = true;
$encodingConverter = new EncodingConverter("utf-8", $replaceHeader, $replaceContent);
$sub = new GuzzleAutoCharsetEncodingSubscriber($encodingConverter);
$client->getEmitter()->attach($sub);
foreach ($urls as $key => $url) {
    $req = $client->createRequest("GET", $url);
    /** @var ResponseInterface $resp */
    $resp = $client->send($req);
    echo "Request to $url:\n";
    echo "Content-Type: " . $resp->getHeader("content-type") . "\n";
    echo $resp->getBody() . "\n==\n\n";
}