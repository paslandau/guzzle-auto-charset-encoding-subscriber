# DEPRECATED ⛔ 

This repository has been deprecated as of 2019-01-27. That code was written a long time ago and has been unmaintained for several years. Thus, repository will now be [archived](https://github.blog/2017-11-08-archiving-repositories/).If you are interested in taking over ownership, feel free to [contact me](https://www.pascallandau.com/about/).

---

# guzzle-auto-charset-encoding-subscriber
[![Build Status](https://travis-ci.org/paslandau/guzzle-auto-charset-encoding-subscriber.svg?branch=master)](https://travis-ci.org/paslandau/guzzle-auto-charset-encoding-subscriber)

Plugin for [Guzzle 4/5](https://github.com/scripts/guzzle) to automatically convert the body of a reponse according to a predefined charset.

## Description

Getting charsets right is hard. In a perfect world, everybody would use unicode (UTF-8) as character encoding for textual web content but that's just not 
gonna happen in the near future, so we have to deal with a lot of different encodings in the wild. Unfortunately that's another layer of complexity on top 
of my application and I really just want it to "work right".

I'm using Guzzle as an underlying library for dealing with HTTP requests and my whole application relies on content beeing encoded in UTF-8. In my locale (Germany)
ISO-8859-1 is still widely used and it really messes up the content of an HTTP response, because Guzzle won't automatically convert ISO-8859-1 to my internally used
UTF-8. So I decided to write this little plugin to convert any input encoding automatically to another output encoding. Headers and meta tags can be optionally adjusted as well.

### Basic Usage
```php

    $client = new Client();
    $converter = new EncodingConverter("utf-8"); // define desired output encoding
    $sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
    $url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso"; // request website with iso-8859-1 encoding
    $req = $client->createRequest("GET", $url);
    $req->getEmitter()->attach($sub);
    $resp = $client->send($req);
```

## Requirements

- PHP >= 5.5 with [mbstring extension](http://php.net/manual/de/book.mbstring.php)
- Guzzle >= 4.0

## Installation

The recommended way to install guzzle-auto-charset-encoding-subscriber is through [Composer](http://getcomposer.org/).

    curl -sS https://getcomposer.org/installer | php

Next, update your project's composer.json file to include GuzzleAutoCharsetEncodingSubscriber:

    {
        "repositories": [ { "type": "composer", "url": "http://packages.myseosolution.de/"} ],
        "minimum-stability": "dev",
        "require": {
             "paslandau/guzzle-auto-charset-encoding-subscriber": "dev-master"
        }
        "config": {
            "secure-http": false
        }
    }

_**Caution:** You need to explicitly set `"secure-http": false` in order to access http://packages.myseosolution.de/ as repository. 
This change is required because composer changed the default setting for `secure-http` to true at [the end of february 2016](https://github.com/composer/composer/commit/cb59cf0c85e5b4a4a4d5c6e00f827ac830b54c70#diff-c26d84d5bc3eed1fec6a015a8fc0e0a7L55)._


After installing, you need to require Composer's autoloader:
```php

    require 'vendor/autoload.php';
```

## Examples

Let's have a look a the differences between a 'normal' guzzle request and a request with a guzzle-auto-charset-encoding-subscriber at first:
```php

    $client = new Client();
    $converter = new EncodingConverter("utf-8",true,true); // define desired output encoding
    $sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
    $url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso";

    $tests = [
        "Using unmodified Guzzle request" => null,
        "Using guzzle-auto-charset-encoding-subscriber" => $sub,
    ];
    foreach($tests as $name => $subscriber) {
        $req = $client->createRequest("GET", $url);
        if($subscriber !== null) {
            $req->getEmitter()->attach($sub);
        }
        $resp = $client->send($req);
        echo "    $name\n";
        echo "    Request to $url:\n";
        echo "    Content-Type: " . $resp->getHeader("content-type") . "\n\n";
        echo $resp->getBody()."\n\n";
    }
```
    
**Output (assuming your editor uses UTF-8 as default)**

    Using unmodified Guzzle request
    Request to http://www.myseosolution.de/scripts/encoding-test.php?enc=iso:
    Content-Type: text/html; charset=iso-8859-1; someOtherRandom="header in here"

    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="iso-8859-1">
            <title>Umlauts everywhere �������</title>
        </head>
        <body>
            <h1>�������</h1>
            
        </body>
    </html>


    Using guzzle-auto-charset-encoding-subscriber
    Request to http://www.myseosolution.de/scripts/encoding-test.php?enc=iso:
    Content-Type: text/html; charset=utf-8; someOtherRandom="header in here"

    <!DOCTYPE html>
    <html>
        <head>
            <meta charset='utf-8' >
            <title>Umlauts everywhere öäüßÖÄÜ</title>
        </head>
        <body>
            <h1>öäüßÖÄÜ</h1>
            
        </body>
    </html>
    
The requested website delivers content in the ISO-8859-1 encoding. The unmodified guzzle request passes exactly what it gets from the website back to us. 
If we're expecting UTF-8 encoded content, we will get the "garbage" result shown above, since the german umlauts won't be recognized. Using the guzzle-auto-charset-encoding-subscriber
will convert the result from the encoding it finds in either the `content-type` header or the websites `meta` tags. To minimize compatibility issues on
subsequent components, the plugin also adjusted the `content-type` header and the `<meta charset='..'>` tag to UTF-8.

The behaviour of the plugin can be modified as follows:

### Adjust the `content-type` header
By default, the `content-type` header is adjusted when the guzzle-auto-charset-encoding-subscriber converts the body of a request into another encoding. 
You can prevent this behaviour by setting the `$replaceHeaders` parameter to `false`:
```php

    $client = new Client();
    $replaceHeaders = false; // prevent the replacement of the content-type header
    $converter = new EncodingConverter("utf-8",$replaceHeaders);
    $sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
    $url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso";
    $req = $client->createRequest("GET", $url);
    $req->getEmitter()->attach($sub);
    $resp = $client->send($req);
```

### Adjust the `meta` tags
By default, the content of a document is _not_ modified (apart from being converted into another encoding). You can explicitly force the guzzle-auto-charset-encoding-subscriber
to adjust the `meta` tags within a document to reflect the new encoding by setting the `$replaceContent` parameter to `true`:
```php

    $client = new Client();
    $replaceHeaders = null; // default
    $replaceContent = true; // force the replacement of the meta tags within the content
    $converter = new EncodingConverter("utf-8",$replaceHeaders, $replaceContent);
    $sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
    $url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso";
    $req = $client->createRequest("GET", $url);
    $req->getEmitter()->attach($sub);
    $resp = $client->send($req);
```
   
Currently, 3 different cases are handled/recognized:

- HTML 4 (uses `<meta http-equiv='content-type' content='text/html; charset=utf-8'>`)
- HTML 5 (uses `<meta charset='utf-8'>`)
- XML (uses `<?xml version='1.0' encoding='utf-8' ?>`)

### Forcing a default input encoding
Some websites use no (or wrong) values for the `content-type` header or the `meta` tags. In those cases, the guzzle-auto-charset-encoding-subscriber can be configured to 
assume a default encoding:
```php

    $client = new Client();
    $replaceHeaders = null; // default
    $replaceContent = null; // default
    $fixedInputEncoding = "iso-8859-1"; // assume "iso-8859-1" as default encoding
    $converter = new EncodingConverter("utf-8",$replaceHeaders, $replaceContent,$fixedInputEncoding);
    $sub = new GuzzleAutoCharsetEncodingSubscriber($converter);
    $url = "http://www.myseosolution.de/scripts/encoding-test.php?enc=iso&header=false&meta=false"; // hide charset from header and meta tags
    $req = $client->createRequest("GET", $url);
    $req->getEmitter()->attach($sub);
    $resp = $client->send($req);
```

## Related plugins

- [guzzle4-charset-subscriber](https://github.com/sasezaki/guzzle4-charset-subscriber) [Guzzle 4]
- [guzzle-plugin-AutoCharsetEncodingPlugin](https://github.com/diggin/guzzle-plugin-AutoCharsetEncodingPlugin) [Guzzle 3]
- [ForceCharsetPlugin](https://gist.github.com/pschultz/6554265) [Guzzle 3]

## Frequently searched questions

- How to change the reponse charset/encoding in Guzzle?
- How to convert the charset/encoding of an response in Guzzle?
- How to force an input/output encoding/charset in Guzzle?
- Guzzle responses appear malformed due to encoding/charset - what to do?
