<?php
/**
 * Created by PhpStorm.
 * User: Hirnhamster
 * Date: 12.10.2014
 * Time: 21:46
 */

namespace paslandau\GuzzleAutoCharsetEncodingSubscriber;


class EncodingResult{
    /**
     * Encoding before the conversion.
     * @var string
     */
    private $originalEncoding;
    /**
     * Encoding after the conversion.
     * @var string
     */
    private $targetEnconding;
    /**
     * Content after the conversion (caution: the content itself remained untouched, e.g. no <meta> tags have been changed)
     * @var string
     */
    private $targetContent;
    /**
     * Headers after the conversion ('content-type' is updated)
     * @var array
     */
    private $targetHeaders;

    /**
     * @param string $originalEncoding
     * @param string $targetEnconding
     * @param array $targetHeaders
     * @param string $targetContent
     */
    function __construct($originalEncoding, $targetEnconding, array $targetHeaders, $targetContent)
    {
        $this->originalEncoding = $originalEncoding;
        $this->targetEnconding = $targetEnconding;
        $this->targetHeaders = $targetHeaders;
        $this->targetContent = $targetContent;
    }

    /**
     * @return string
     */
    public function getOriginalEncoding()
    {
        return $this->originalEncoding;
    }

    /**
     * @param string $originalEncoding
     */
    public function setOriginalEncoding($originalEncoding)
    {
        $this->originalEncoding = $originalEncoding;
    }

    /**
     * @return string
     */
    public function getTargetContent()
    {
        return $this->targetContent;
    }

    /**
     * @param string $targetContent
     */
    public function setTargetContent($targetContent)
    {
        $this->targetContent = $targetContent;
    }

    /**
     * @return string
     */
    public function getTargetEnconding()
    {
        return $this->targetEnconding;
    }

    /**
     * @param string $targetEnconding
     */
    public function setTargetEnconding($targetEnconding)
    {
        $this->targetEnconding = $targetEnconding;
    }

    /**
     * @return array
     */
    public function getTargetHeaders()
    {
        return $this->targetHeaders;
    }

    /**
     * @param array $targetHeaders
     */
    public function setTargetHeaders($targetHeaders)
    {
        $this->targetHeaders = $targetHeaders;
    }


}