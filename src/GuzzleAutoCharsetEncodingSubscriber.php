<?php
namespace paslandau\GuzzleAutoCharsetEncodingSubscriber;
use GuzzleHttp\Event\AbstractTransferEvent;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Stream\Stream;
use paslandauExceptionUtility\ExceptionUtil;
use paslandau\WebUtility\EncodingConversion\EncodingConverterInterface;

/**
 * Created by PhpStorm.
 * User: Hirnhamster
 * Date: 12.10.2014
 * Time: 14:55
 */

class GuzzleAutoCharsetEncodingSubscriber implements SubscriberInterface{
    /**
     * @var EncodingConverterInterface
     */
    private $converter;

    /**
     * @param EncodingConverterInterface $converter
     */
    function __construct(EncodingConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public function getEvents()
    {
        return array(
            'complete' => ['convert'],
            'error' => ['convert']
        );
    }

    public function convert(AbstractTransferEvent $event)
    {
            $response = $event->getResponse();
            if ($response === null) {
                return;
            }
            $stream = $response->getBody();
            if ($stream === null) { // no body - nothing to convert
                return;
            }
            $headers = $response->getHeaders();
            // represent the headers as a string
            foreach ($headers as $name => $values) {
                $headers[$name] = implode("; ", $values);
            }
            $content = $stream->__toString();
            $result = $this->converter->convert($headers, $content);
            if ($result !== null) {
                $body = new Stream(fopen('php://temp', 'r+'));// see Guzzle 4.1.7 > GuzzleHttp\Adapter\Curl\RequestMediator::writeResponseBody
                $response->setBody($body);
                $body->write($result->getTargetContent());
                $response->setHeaders($result->getTargetHeaders());
            }
    }
}