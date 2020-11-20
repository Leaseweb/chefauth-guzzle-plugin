leaseweb/chefauth-guzzle-plugin
===============================

A guzzle middleware handling all authentication for Chef server API.


requirements
------------

- PHP 7.0
- Guzzle 7


installation
------------

First you need Guzzle, offcourse.

Add the `leaseweb/chefauth-guzzle-plugin` as a dependency to your project:

    $ php composer.phar require "leaseweb/chefauth-guzzle-plugin":"2.0.0"

Composer will install the plugin to your project's vendor/leaseweb directory.

You are now ready to use the plugin.


usage
-----

Create a new guzzle client pointing to your chef server:

    <?php
    require_once 'vendor/autoload.php';

    use GuzzleHttp\Client;
    use GuzzleHttp\HandlerStack;
    use GuzzleHttp\Handler\CurlHandler;
    use LeaseWeb\ChefGuzzle\Middleware\ChefAuthMiddleware;

    $handler = new CurlHandler();

    $stack = HandlerStack::create($handler);
    $stack->push(new ChefAuthMiddleware('janedoe', 'path/to/key.pem'));

    $client = new Client([
        'base_uri' => 'https://my.chef.server.com/organizations/acme',
        'handler' => $stack
    ]);

    $environments = $client->get("/environments");


Read more about guzzle here http://docs.guzzlephp.org/en/stable/index.html


license
-------

MIT
