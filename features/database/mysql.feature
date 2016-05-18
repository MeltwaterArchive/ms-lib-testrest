Feature: Testing the mysql driver

  Background:
    Given a file named "behat.yml" with:
      """
      default:
          formatters:
              progress: ~
          extensions:
              DataSift\BehatExtension:
                  base_url: http://localhost:8080/
                  database:
                    driver: mysql
                    dbname: testrest
                    host: 127.0.0.1
                    port: ~
                    username: testrest
                    password: testrest
                    schema: schema.sql
                    data: data.sql

          suites:
              default:
                  contexts:
                    - 'DataSift\BehatExtension\Context\RestContext'
                    - 'DataSift\BehatExtension\Context\DatabaseContext'
      """
    And a file named "schema.sql" with:
      """
        DROP TABLE IF EXISTS `test`;
        CREATE TABLE IF NOT EXISTS `test` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(255) NOT NULL,
          PRIMARY KEY (`id`)
        );
      """
    And a file named "data.sql" with:
      """
        INSERT INTO test (name) VALUES
         ('alpha'),
         ('beta'),
         ('gamma');
      """

  Scenario: Test for API tests
    Given a file named "features/mysql.feature" with:
      """
      Feature: Testing

        Scenario: Check database seed data
          Given that the "test" table has "3" rows
      """
    When I run "behat features/mysql.feature"
    Then it should pass with:
      """
      .

      1 scenario (1 passed)
      1 step (1 passed)
      """
