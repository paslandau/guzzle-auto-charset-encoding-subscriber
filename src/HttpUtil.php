<?php
/**
 * Created by PhpStorm.
 * User: Hirnhamster
 * Date: 12.10.2014
 * Time: 16:44
 */

namespace paslandau\GuzzleAutoCharsetEncodingSubscriber;

/**
 * HTTP Headers Util - Header value parsing utility functions
 *
 * This class is based on:
 * <http://search.cpan.org/author/GAAS/libwww-perl-5.65/lib/HTTP/Headers/Util.pm>
 * by Gisle Aas.
 * The text here is copied from the documentation of the above, obviously
 * slightly modified as this is PHP not Perl.
 *
 * SYNOPSIS
 *
 * $values = HTTP_Headers_Util::split_header_words($header_value);
 *
 * DESCRIPTION
 *
 * This class provides a few functions that helps parsing and
 * construction of valid HTTP header values.
 * @static
 */
class HttpUtil
{
    /**
     * split_header_words
     *
     * This function will parse the header values given as argument into a
     * array containing key/value pairs.  The function
     * knows how to deal with ",", ";" and "=" as well as quoted values after
     * "=".  A list of space separated tokens are parsed as if they were
     * separated by ";".
     *
     * If the $header_values passed as argument contains multiple values,
     * then they are treated as if they were a single value separated by
     * comma ",".
     *
     * This means that this function is useful for parsing header fields that
     * follow this syntax (BNF as from the HTTP/1.1 specification, but we relax
     * the requirement for tokens).
     *
     *   headers           = #header
     *   header            = (token | parameter) *( [";"] (token | parameter))
     *
     *   token             = 1*<any CHAR except CTLs or separators>
     *   separators        = "(" | ")" | "<" | ">" | "@"
     *                     | "," | ";" | ":" | "\" | <">
     *                     | "/" | "[" | "]" | "?" | "="
     *                     | "{" | "}" | SP | HT
     *
     *   quoted-string     = ( <"> *(qdtext | quoted-pair ) <"> )
     *   qdtext            = <any TEXT except <">>
     *   quoted-pair       = "\" CHAR
     *
     *   parameter         = attribute "=" value
     *   attribute         = token
     *   value             = token | quoted-string
     *
     * Each header is represented by an anonymous array of key/value
     * pairs.  The value for a simple token (not part of a parameter) is null.
     * Syntactically incorrect headers will not necessary be parsed as you
     * would want.
     *
     * This is easier to describe with some examples:
     *
     *    split_header_words('foo="bar"; port="80,81"; discard, bar=baz');
     *    split_header_words('text/html; charset="iso-8859-1");
     *    split_header_words('Basic realm="\"foo\\bar\""');
     *    split_header_words("</TheBook/chapter,2>;         rel=\"pre,vious\"; title*=UTF-8'de'letztes%20Kapitel, </TheBook/chapter4>;rel=\"next\"; title*=UTF-8'de'n%c3%a4chstes%20Kapitel");
     *
     * will return
     *
     *    [foo=>'bar', port=>'80,81', discard=>null], [bar=>'baz']
     *    ['text/html'=>null, charset=>'iso-8859-1']
     *    [Basic=>null, realm=>'"foo\bar"']
     *    ["</TheBook/chapter,2>" => null, "rel" => "pre,vious", "title*" => "UTF-8'de'letztes%20Kapitel" ], ["</TheBook/chapter4>" => null, "rel" => "next", "title*" => "UTF-8'de'n%c3%a4chstes%20Kapitel" ]
     *
     * @param mixed $header_values string or array
     * @throws \Exception
     * @return array
     */
    public static function splitHeaderWords($header_values)
    {
        if (!is_array($header_values)) $header_values = array($header_values);

        $result = array();
        foreach ($header_values as $header) {
            $cur = array();
            while ($header) {
                $key = '';
                $val = null;
                // 'token' or parameter 'attribute'
                if (preg_match('/^\s*(<[^>]*>)(.*)/', $header, $match)) {
                    $key = $match[1];
                    $header = $match[2];
                    $cur[$key] = null;
                } // 'token' or parameter 'attribute'
                elseif (preg_match('/^\s*(=*[^\s=;,]+)(.*)/', $header, $match)) {
                    $key = $match[1];
                    $header = $match[2];
                    // a quoted value
                    if (preg_match('/^\s*=\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(.*)/', $header, $match)) {
                        $val = $match[1];
                        $header = $match[2];
                        // remove backslash character escape
                        $val = preg_replace('/\\\\(.)/', "$1", $val);
                        // some unquoted value
                    } elseif (preg_match('/^\s*=\s*([^;,\s]*)(.*)/', $header, $match)) {
                        $val = trim($match[1]);
                        $header = $match[2];
                    }
                    // add details
                    $cur[$key] = $val;
                    // reached the end, a new 'token' or 'attribute' about to start
                } elseif (preg_match('/^\s*,(.*)/', $header, $match)) {
                    $header = $match[1];
                    if (count($cur)) $result[] = $cur;
                    $cur = array();
                    // continue
                } elseif (preg_match('/^\s*;(.*)/', $header, $match)) {
                    $header = $match[1];
                } elseif (preg_match('/^\s+(.*)/', $header, $match)) {
                    $header = $match[1];
                } else {
                    throw new \Exception('This should not happen: "' . $header . '"');
                    return $result;
                }
            }
            if (count($cur)) $result[] = $cur;
        }
        return $result;
    }

    /*
    * join_header_words
    *
    * This will do the opposite of the conversion done by split_header_words().
    * It takes a list of anonymous arrays as arguments (or a list of
    * key/value pairs) and produces a single header value.  Attribute values
    * are quoted if needed.
    *
    * Example:
    *
    *    join_header_words(array(array("text/plain" => null, "charset" => "iso-8859/1")));
    *    join_header_words(array("text/plain" => null, "charset" => "iso-8859/1"));
    *
    * will both return the string:
    *
    *    text/plain; charset="iso-8859/1"
    *
    * @param array $header_values
    * @return string
    */
    public static function joinHeaderWords($header_values)
    {
        if (!is_array($header_values) || !count($header_values)) return false;
        if (!isset($header_values[0])) $header_values = array($header_values);

        $spaces = "\\s";
        $ctls = "\\x00-\\x1F\\x7F"; //@see http://stackoverflow.com/a/1497928/413531
        $tspecials = "()<>@,;:<>/[\\]?.=\"\\\\";
        $tokenPattern = "#^[^{$spaces}{$ctls}{$tspecials}]+$#";
        $result = array();
        foreach ($header_values as $header) {
            $attr = array();
            foreach ($header as $key => $val) {
                if (isset($val)) {
                    if (preg_match($tokenPattern, $val, $match)) {
                        $key .= "=$val";
                    } else {
                        $val = preg_replace('/(["\\\\])/', "\\\\$1", $val);
                        $key .= "=\"$val\"";
                    }
                }
                $attr[] = $key;
            }
            if (count($attr)) $result[] = implode('; ', $attr);
        }
        return implode(', ', $result);
    }
} 