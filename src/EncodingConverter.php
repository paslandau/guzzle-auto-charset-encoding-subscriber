<?php
/**
 * Created by PhpStorm.
 * User: Hirnhamster
 * Date: 12.10.2014
 * Time: 15:39
 */

namespace paslandau\FixEncodingSubscriber;

class EncodingConverter implements EncodingConverterInterface{

    /**
     * @var string
     */
    private $toEncoding;
    /**
     * @var bool|null
     */
    private $replaceHeaders;
    /**
     * @var bool|null
     */
    private $replaceContent;

    /**
     * If set, this encoding is used as original encoding, regardless if headers or meta tags state something else.
     * @var null|string
     */
    private $fixedInputEncoding;

    /**
     * @param $toEncoding
     * @param bool|null $replaceHeaders
     * @param bool|null $replaceContent
     * @param null|string $fixedInputEncoding [optional]. Default: null.
     */
    function __construct($toEncoding, $replaceHeaders = null, $replaceContent = null, $fixedInputEncoding = null)
    {
        $this->toEncoding = $toEncoding;
        if($replaceHeaders === null){
            $replaceHeaders = true;
        }
        $this->replaceHeaders = $replaceHeaders;

        if($replaceContent === null){
            $replaceContent = false;
        }
        $this->replaceContent = $replaceContent;
        $this->fixedInputEncoding = $fixedInputEncoding;
    }


    /**
     * Converts the given $content to the charset defined by $toEncoding. The original encoding is defined by (in order):
     * - $this->fixedInputEncoding
     * - the 'charset' parameter of the 'content-type' header
     * - the meta information in the body of an HTML (content-type: text/html)or XML (content-type: text/xml or application/xml) document
     *
     * If the original encoding could not be determined, null is returned. If the original encoding is the same as the target encoding, this method will also return null.
     *
     * Otherwise an object of type EncodingResult is returned. Please see the description of the properties of said class.
     * @param array $headers
     * @param string $content
     * @return EncodingResult|null
     */
    public function convert(array $headers, $content)
    {
        $encodings = [
            "fixed" => $this->fixedInputEncoding,
            "header" => null,
            "content" => null,
        ];
        $replacements = [
            "fixed" => null,
            "header" => null,
            "content" => null,
        ];
        // else, get content-type header
        $contentType = $this->getByCaseInsensitiveKey($headers,"content-type");
        if ($contentType === null) {
            $contentType = "";
        }
        $parsed = HttpUtil::splitHeaderWords($contentType);
        if(count($parsed) > 0){
            $parsed = reset($parsed);
        }
        //check the header
        $encoding = $this->getByCaseInsensitiveKey($parsed,"charset");
        if($encoding !== null){
            $encodings["header"] = $encoding;
        }
        $newParsed = $this->setByCaseInsensitiveKey($parsed,"charset",$this->toEncoding);
        $replacements["header"]["content-type"] = HttpUtil::joinHeaderWords($newParsed);
        // else, check the body
        if(preg_match("#^text/html#i",$contentType)){
            // find http-equiv
            $patternHtml4 = "#<meta[^>]+http-equiv=[\"']?content-type[\"']?[^>]*?>#i"; // html 4 - e.g. <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
            $patternHtml5 = "#(?P<before><meta[^>]+?)charset=[\"'](?P<charset>[^\"' ]+?)[\"' ](?P<after>[^>]*?>)#i"; // e.g. <meta charset=iso-8859-1> - for html 5 http://webdesign.about.com/od/metatags/qt/meta-charset.htm
            if(preg_match($patternHtml4,$content,$match)){
                $pattern = "#(?P<before>.*)content=([\"'])(?P<content>.*?)\\2(?P<after>.*)#";
                if(preg_match($pattern,$match[0],$innerMatch)){
                    $parsed = HttpUtil::splitHeaderWords($innerMatch["content"]);
                    if(count($parsed) > 0){
                        $parsed = reset($parsed);
                    }
                    $encodings["content"] = $this->getByCaseInsensitiveKey($parsed,"charset");
                    $newParsed = $this->setByCaseInsensitiveKey($parsed,"charset", $this->toEncoding);
                    $newContent = HttpUtil::joinHeaderWords($newParsed);
                    $newMeta = $innerMatch["before"]."content='".$newContent."'".$innerMatch["after"];
                    $replacements["content"][$match[0]] = $newMeta;
                }
            }elseif(preg_match($patternHtml5,$content,$match)) {
                $encodings["content"] = $match["charset"];
                $newMeta = $match["before"]."charset='".$this->toEncoding."' ".$match["after"];
                $replacements["content"][$match[0]] = $newMeta;
            }
        }elseif(preg_match("#^(text|application)/xml#i",$contentType)){ // see http://stackoverflow.com/a/3272572/413531
            $patternXml = "#(?P<before><\\?xml[^>]+?)encoding=[\"'](?P<charset>[^\"']+?)[\"'](?P<after>[^>]*?>)#i";
            if(preg_match($patternXml,$content,$match)) {
                $encodings["content"] = $match["charset"];
                $newMeta = $match["before"]."encoding='".$this->toEncoding."' ".$match["after"];
                $replacements["content"][$match[0]] = $newMeta;
            }
        }
        $finalEncoding = null;
        foreach($encodings as $type => $encoding){
            if($encoding !== null){
                $finalEncoding = $encoding;
                break;
            }
        }
        if($finalEncoding === null){
//            echo "No encoding found, doing nothing..\n";
            return null;
        }
        if(strcasecmp($finalEncoding, $this->toEncoding) === 0){
//            echo "'$toEncoding' is already set, doing nothing..\n";
            return null;
        }
        $converted = mb_convert_encoding($content, $this->toEncoding, $finalEncoding);
        $headers_new = $headers;
        if($this->replaceHeaders){
            foreach ($replacements["header"] as $headerKey => $value) {
                $headers_new = $this->setByCaseInsensitiveKey($headers_new, $headerKey, $value);
            }
        }
        $converted_new = $converted;
            if($this->replaceContent) {
                if ($replacements["content"] !== null) {
                    foreach ($replacements["content"] as $oldContent => $newContent) {
                        $converted_new = str_replace($oldContent, $newContent, $converted_new);
                    }
                }
            }
        $result = new EncodingResult(
            $finalEncoding,
            $this->toEncoding,
            $headers_new,
            $converted_new
        );
        return $result;
    }

    /**
     * @param array $words
     * @param $key
     * @return mixed|null
     */
    private function getByCaseInsensitiveKey(array $words, $key){
        foreach ($words as $headerWord => $value) {
            if (strcasecmp($headerWord, $key) === 0) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param array $words
     * @param $key
     * @param $newValue
     * @return array
     */
    private function setByCaseInsensitiveKey(array $words, $key, $newValue){
        foreach ($words as $headerWord => $value) {
            if (strcasecmp($headerWord, $key) === 0) {
                $words[$headerWord] = $newValue;
                return $words;
            }
        }
        $words[$key] = $newValue;
        return $words;
    }
}