DataSift\\TestRest
==================

## Description

This library contains utility classes to test end-to-end RESTful services using Behat.


## Getting started

First, you need to install all dependencies (you'll need [composer](https://getcomposer.org/)).
```bash
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
```

```bash
make build_dev
```

## Running Tests

To run our unit tests, run `make test`.

To run the database tests, you'll need a database and a testing user with the right privileges:

```sql
CREATE DATABASE IF NOT EXISTS testrest_test;
GRANT ALL ON testrest_test.* TO 'testrest'@'%' IDENTIFIED BY 'testrest';
FLUSH PRIVILEGES;
```


## Coding standards

We follow the PSR2 coding standard. To see any errors in your code, run `make phpcs`.
We also use a tool to detect any code smells. To run it, use `make phpmd`
You can also issue the command `make qa` to execute all tests at once, or `make qa_all` to also check the unit tests.


## Advanced commands
Please issue the command `make help` to see all available options.


## Installation

This project requires PHP 5.4.0+ to use the PHP built-in web server.


* Create a composer.json in your projects root-directory and include this project:

```json
{
    "require-dev": {
        "datasift/ms-lib-testrest": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:datasift/ms-lib-testrest.git"
        }
    ]
}
```

* Create a behat.yml file in the root directory of your project like the one in test/behat.yml

* Create a test/features folder in your project like the one in test/features

* Create a "btest" entry in the makefile like the one in the makefile of this project, to start the PHP built-in server and execute the Behat tests.


## Developer(s) Contact

* Nicola Asuni <nicola.asuni@datasift.com>
