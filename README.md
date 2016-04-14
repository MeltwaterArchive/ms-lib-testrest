testrest-extension
==================

## Description

This behat extension provides utility classes to test end-to-end RESTful services using behat.

Installation
------------

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `datasift/testrest-extension`.

	"require-dev": {
		"datasift/testrest-extension": "3.*"
	}

Next, update Composer from the Terminal:

    composer update

Activate extension by specifying its class in your behat.yml:

```yml
# behat.yml
default:
  extensions:
      DataSift\TestRestExtension:
          base_url: http://localhost:8080/

  suites:
      default:
          contexts:
            - 'DataSift\TestRestExtension\Context\RestContext'
```

Database
--------

Supported Drivers
- mysql
- sqlite

Cache
-----

Supported drivers
- memcached

Mountebank
----------


File
----



Testing
-------

To test the library itself, run the tests:

    composer test

Contributing
------------

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

Credits And Developer Contacts
-------

- [nicolaasuni](https://github.com/nicolaasuni)
- [nathanmac](https://github.com/nathanmac)
- [shwetsai](https://github.com/shwetsai)
- [mheap](https://github.com/mheap)
- [All Contributors](../../contributors)

License
-------

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
