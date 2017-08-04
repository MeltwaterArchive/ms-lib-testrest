behat-extension
==================

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datasift/behat-extension.svg?style=flat-square)](https://packagist.org/packages/datasift/behat-extension)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/datasift/ms-lib-testrest/master.svg?style=flat-square)](https://travis-ci.org/datasift/ms-lib-testrest)
[![Total Downloads](https://img.shields.io/packagist/dt/datasift/behat-extension.svg?style=flat-square)](https://packagist.org/packages/datasift/behat-extension)

## Description

This behat extension provides utility classes to test end-to-end RESTful services using behat.

Installation
------------

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `datasift/behat-extension`.

If you need support for Guzzle 4-5, use testrest-extension 4.x.

	"require-dev": {
		"datasift/testrest-extension": "4.*"
	}
	
If you need support for Guzzle 6, use testrest-extension 5.x.

	"require-dev": {
		"datasift/testrest-extension": "5.*"
	}

Next, update Composer from the Terminal:

    composer update

Activate extension by specifying its class in your behat.yml:

```yml
# behat.yml
default:
  extensions:
      DataSift\BehatExtension:
          base_url: http://localhost:8080/

  suites:
      default:
          contexts:
            - 'DataSift\BehatExtension\Context\RestContext'
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
