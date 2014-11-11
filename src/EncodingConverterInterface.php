<?php
/**
 * Created by PhpStorm.
 * User: Hirnhamster
 * Date: 12.10.2014
 * Time: 14:57
 */

namespace paslandau\GuzzleAutoCharsetEncodingSubscriber;


interface EncodingConverterInterface {
    /**
     * @param array $headers
     * @param string $content
     * @return EncodingResult|null
     */
    public function convert(array $headers, $content);
} 