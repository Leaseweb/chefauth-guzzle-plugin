leaseweb/chefauth-guzzle-plugin
===============================

A guzzle (v3) plugin handling all authentication for Chef server API.


requirements
------------

- PHP 5.3
- Guzzle 3


installation
------------

First you need Guzzle, offcourse.

Add the `leaseweb/chef-guzzle-plugin` as a dependency to your project:

    $ php composer.phar require "leaseweb/chef-guzzle-plugin":"1.0.0"

Composer will install the plugin to your project's vendor/leaseweb directory.

You are now ready to use the plugin.


usage
-----

Create a new guzzle client pointing to your chef server:

    // Supply your client name and location of the private key.
    $chefAuthPlugin = new \LeaseWeb\ChefGuzzle\Plugin\ChefAuth\ChefAuthPlugin("client-name", "/tmp/client-name.pem");

    // Create a new guzzle client
    $client = new \Guzzle\Http\Client('https://manage.opscode.com');
    $client->addSubscriber($chefAuthPlugin);


    // Now you can make calls to the chef server
    $response = $client->get('/organizations/my-organization/nodes')->send();

    $nodes = $response->json();


Read more about guzzle here http://guzzle3.readthedocs.org/docs.html


license
-------

MIT
