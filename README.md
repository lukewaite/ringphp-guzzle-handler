# RingPHP Guzzle Handler
[![Latest Version on Packagist](https://img.shields.io/packagist/v/lukewaite/ringphp-guzzle-handler.svg?style=flat-square)](https://packagist.org/packages/lukewaite/ringphp-guzzle-handler)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/lukewaite/ringphp-guzzle-handler.svg?style=flat-square)](https://packagist.org/packages/lukewaite/ringphp-guzzle-handler)

## Usage

### Installing

This package can be installed with composer.

    $ composer require lukewaite/ringphp-guzzle-handler

### Elasticsearch

```php
$guzzleHandler  = new LukeWaite\RingPhpGuzzleHandler\GuzzleHandler();

$client = Elasticsearch\ClientBuilder::create()
            ->setHandler($guzzleHandler)
            ->build();
```

Optionally, you may create a Guzzle client manually, and pass it through to the constructor:
```php
$guzzle = new GuzzleHttp\Client();
$guzzleHandler  = new LukeWaite\RingPhpGuzzleHandler\GuzzleHandler($guzzle);

$client = Elasticsearch\ClientBuilder::create()
            ->setHandler($guzzleHandler)
            ->build();
```

## What have you done?
I've built a [RingPHP][ringphp] Handler that uses Guzzle as the transport.

### You've done wot mate?
Yes - I built a handler for RingPHP (an older GuzzleHttp project) that
uses Guzzle 6 as the transport.

### Reasoning
The ElasticSearch PHP SDK uses the RingPHP client library under the
covers. You can provide a `Handler` when creating the client, but it has
to be a RingPHP handler.

NewRelic supports tracking external requests for Guzzle, but not for
RingPHP. Using this handler means we can get more accurate instrumentation
on our transactions.

#### Example NewRelic APM Chart, Before and After Deployment
![newrelic before and after](https://lukewaite.ca/images/2017-07-15-newrelic-elasticsearch/newrelic-instrumentation.png)

## How true to RingPHP is this?
The spec for [implementing handlers][implementing-handlers] has been
followed, but in some cases I've had to go out of my way to tune this
for the ElasticSearch PHP SDK.

#### $response `body`
You're supposed to be able to return a [lot of different types][response]
here, but ElasticSearch expects it to be only a stream, so that's what we
return.

#### $response `transfer_stats`
Transfer stats is supposed to be an arbitrary array of stats provided by
the handler, but it turns out ElasticSearch expects some pretty specific
stuff from the default CURL handler to be in there.

[implementing-handlers]: http://ringphp.readthedocs.io/en/latest/client_handlers.html#implementing-handlers
[response]: http://ringphp.readthedocs.io/en/latest/spec.html#responses
[ringphp]: https://github.com/guzzle/RingPHP
