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
                  cache:
                    driver: memcached
                    host: 127.0.0.1
                    port: ~

          suites:
              default:
                  contexts:
                    - 'DataSift\BehatExtension\Context\RestContext'
                    - 'DataSift\BehatExtension\Context\CacheContext'
      """

  Scenario: Test for API tests
    Given a file named "features/memcached.feature" with:
      """
      Feature: Testing

        Scenario: Check database seed data
          When I make a "GET" request to "/echo"
          Then the response status code should be "200"
          And the "last-run" property equals "false"
          When I make a "GET" request to "/echo"
          Then the response status code should be "200"
          And the value of the "last-run" property matches the pattern "/^[0-9]{4}[\-][0-9]{2}[\-][0-9]{2} [0-9]{2}[:][0-9]{2}[:][0-9]{2}$/"
      """
    When I run "behat features/memcached.feature"
    Then it should pass with:
      """
      ......

      1 scenario (1 passed)
      6 steps (6 passed)
      """
