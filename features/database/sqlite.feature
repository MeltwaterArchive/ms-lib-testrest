Feature: Testing the sqlite driver

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
                    driver: sqlite
                    path: tmp.sqlite
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
          `id` INT NOT NULL,
          `name` VARCHAR(255) NOT NULL,
          PRIMARY KEY (`id`)
        );
      """
    And a file named "data.sql" with:
      """
        INSERT INTO `test` (`id`, `name`) VALUES
         (1, 'alpha');
        INSERT INTO `test` (`id`, `name`) VALUES
         (2, 'beta');
        INSERT INTO `test` (`id`, `name`) VALUES
         (3, 'gamma');
      """

  Scenario: Test for API tests
    Given a file named "features/sqlite.feature" with:
      """
      Feature: Testing

        Scenario: Check database seed data
          Given that the "test" table has "3" rows
      """
    When I run "behat features/sqlite.feature"
    Then it should pass with:
      """
      .

      1 scenario (1 passed)
      1 step (1 passed)
      """
