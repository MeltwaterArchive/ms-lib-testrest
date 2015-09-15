# Testing DataSift PHP RESTful services

This guide shows how to use this project with an existing Datasift PHP RESTful service for end-to-end testing.


## Testing database

If the PHP service uses an internal database, then we need to create a testing database locally and in the integration environment (i.e. GoCD):

1. add the following note to the README.md file (replace PROJECTNAME with your project name):

```
The behat tests requires a MySQL testing database and a user configured as below:
```sql
CREATE DATABASE IF NOT EXISTS PROJECTNAME_test;
GRANT ALL ON PROJECTNAME_test.* TO 'PROJECTNAME'@'%' IDENTIFIED BY 'PROJECTNAME';
FLUSH PRIVILEGES;
``````

2. execute the above SQL code on the MySQL database in your development machine to be able to run the test locally.

3. Add the testing database to GoCD:

https://github.com/datasift/ms-gocd-chef/blob/develop/cookbooks/gocd/files/default/mysql/mysqldbs.sql
```sql
CREATE DATABASE IF NOT EXISTS PROJECTNAME_test;
GRANT ALL ON PROJECTNAME_test.* TO 'PROJECTNAME'@'%' IDENTIFIED BY 'PROJECTNAME';
```
The new database will be available in every GoCD agent once the code will be merged to the master branch of ms-gocd-chef and the pipelines "ms-gocd-chef" and "run-chef-on-gocd-servers" will be successfully completed.

4. add the following files to the PHP service project:
```
 test/resources/database/schema.sql   # contains the database schema SQL
 test/resources/database/data.sql     # contains the testing data
```

## Add project dependencies

This project (ms-lib-testrest) must be added as a composer dependency, so you have to add the following sections to the composer.json file of the PHP service to test:

```json
{
    "require-dev": {
        "datasift/ms-lib-testrest": "dev-develop"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:datasift/ms-lib-testrest.git"
        }
    ]
}
```

Delete the composer.lock file and vendor directory, and run the "make build_dev" to download all dependencies.


## Update the Makefile

Update the makefile in the root of the project to contain the following sections:

1. Add the "btest" entry to the ".PHONY" section.

2. Set the PORT number to 8081
```
# Default port number for the testing server
PORT?=8081
```

3. Add the following line to the "help" target:
```
	@echo "    make btest       : Run the Behat tests (behavior test)"
```

4. Add the "btest" target:
```
# Run the Behat tests (behavior test)
btest:
	APRE_ENVIRONMENT_OVERRIDE=1 APRE_ENVIRONMENT=testing nohup $(shell which php) -t src/public -S localhost:$(PORT) > target/server.log 2>&1 & echo $$! > target/server.pid
	APRE_ENVIRONMENT_OVERRIDE=1 APRE_ENVIRONMENT=testing ./src/vendor/bin/behat --config ./behat.yml -f pretty,junit,html --out ,target/behat,target/behat.html ; echo $$? > target/behat.exit; kill -9 `cat target/server.pid` ; exit `cat target/behat.exit`
```

5. Add the "btest" target to the "qa" target:
```
qa: test btest phpcs phpmd
```

## Create the behat configuration file

Copy the https://github.com/datasift/ms-lib-testrest/blob/develop/test/behat.yml file in the root of the PHP service project and modify it to reflect the current configuration.



## Create the testing folder

1. Copy the "test/features" directory of ms-lib-testrest in the test directory of the PHP service project.
https://github.com/datasift/ms-lib-testrest/tree/develop/test/features

2. Overwrite the test/features/bootstrap/bootstrap.php with the project testing bootstrap.php file, or edit it to include the minimum required features.


## Create testing scenarios

Create the ".feature" files in the test/feature directory to contain the various API features to test.
Look at the provided api.feature file for example.


## Execute the tests

To execute the tests you can type "make btest" or "make qa_all" to execute all available tests.

