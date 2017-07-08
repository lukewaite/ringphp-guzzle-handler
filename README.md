# RingPHP Guzzle Handler

### What have you done?
I've built a [RingPHP](https://github.com/guzzle/RingPHP) Handler that
uses Guzzle as the transport.

### You've done wot mate?
Yes - I built a transport for RingPHP (an older GuzzleZHttp project) that
uses Guzzle 6 as the transport.

### Reasoning
The ElasticSearch PHP SDK uses the RingPHP client library under the
covers. You can provide a `Handler` when creating the client, but it has
to be a RingPHP handler.

NewRelic supports tracking external requests for Guzzle, but not for
RingPHP. Using this handler means we can get more accurate instrumentation
on our transactions.

### How true to RingPHP is this?
The spec for [implementing handlers][implementing-handlers] has been
followed, but in some cases I've had to go out of my way to tune this
for the ElasticSearch PHP SDK.

#### $response `body`
You're supposed to be able to return a [lot of different types](http://ringphp.readthedocs.io/en/latest/spec.html#responses) here, but
ElasticSearch expects it to be only a stream, so that's what we return.

#### $response `transfer_stats`
Transfer stats is supposed to be an arbitrary array of stats provided by
the handler, but it turns out ElasticSearch expects some pretty specific
stuff from the default CURL handler to be in there.


[implementing-handlers]: http://ringphp.readthedocs.io/en/latest/client_handlers.html#implementing-handlers