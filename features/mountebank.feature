Feature: Testing mountebank mocking

  Background:
    Given a file named "behat.yml" with:
      """
      default:
          formatters:
              progress: ~
          extensions:
              DataSift\TestRestExtension:
                  base_url: http://localhost:4546/
                  mountebank: ~

          suites:
              default:
                  contexts:
                    - 'DataSift\TestRestExtension\Context\RestContext'
                    - 'DataSift\TestRestExtension\Context\MountebankContext'
      """

  Scenario: Testing Mountebank not running
    Given a file named "features/mountebank-not-running.feature" with:
      """
      Feature: Mountebank testing

        Scenario: Testing Mountebank not running
          Given Mountebank is not running
      """
    When I run "behat features/mountebank-not-running.feature"
    Then it should pass with:
      """
      .

      1 scenario (1 passed)
      1 step (1 passed)
      """

  Scenario: Testing Mountebank Scenario Tagging
    Given a file named "json/user.json" with:
      """
      {
          "name": "Michael",
          "role": "Developer",
          "location": {
              "city": "Reading"
          }
      }
      """
    And a file named "features/mountebank-scenario.feature" with:
      """
      Feature: Testing Mountebank

        @mountebank @billing @bicker @annoy
        Scenario: Example test
          Given Mountebank is running
          And a mock exists at "/kirkland/capacity" it should return "200" with the body:
          '''
            { "capacity": 500000, "usage": 39274 }
          '''
          And a mock exists at "/foo" it should return "200" with the body:
          '''
            bar
          '''
          And the mocks are created
          When I make a "GET" request to "/kirkland/capacity"
          Then the response status code should be "200"
          And the response is JSON
          And the response body JSON equals
          '''
            { "capacity": 500000, "usage": 39274 }
          '''
          When I make a "GET" request to "/foo"
          Then the response status code should be "200"
          And the response body equals
          '''
            bar
          '''

        @mountebank
        Scenario: Example injection
          Given Mountebank is running
          And a mock exists at "/inject" it should return "200" with a generated response where:
          '''
            foo = input.bar
            baz.bee = abc
          '''
          And the mocks are created

        @billing @bicker @mountebank @annoy
        Scenario: Example using fixture
          Given Mountebank is running
          And a mock exists at "/auth/user" it should return "200" with the fixture "json/user"
          And the mocks are created
      """
    When I run "behat features/mountebank-scenario.feature"
    Then it should pass with:
      """
      .................

      3 scenarios (3 passed)
      17 steps (17 passed)
      """

  Scenario: Testing Mountebank Feature Tagging
    Given a file named "json/user.json" with:
      """
      {
          "name": "Michael",
          "role": "Developer",
          "location": {
              "city": "Reading"
          }
      }
      """
    And a file named "features/mountebank-scenario.feature" with:
      """
      @billing @bicker @mountebank @annoy
      Feature: Testing Mountebank

        Scenario: Example test
          Given Mountebank is running
          And a mock exists at "/kirkland/capacity" it should return "200" with the body:
          '''
            { "capacity": 500000, "usage": 39274 }
          '''
          And a mock exists at "/foo" it should return "200" with the body:
          '''
            bar
          '''
          And the mocks are created
          When I make a "GET" request to "/kirkland/capacity"
          Then the response status code should be "200"
          And the response is JSON
          And the response body JSON equals
          '''
            { "capacity": 500000, "usage": 39274 }
          '''
          When I make a "GET" request to "/foo"
          Then the response status code should be "200"
          And the response body equals
          '''
            bar
          '''

        @mountebank
        Scenario: Example injection
          Given Mountebank is running
          And a mock exists at "/inject" it should return "200" with a generated response where:
          '''
            foo = input.bar
            baz.bee = abc
          '''
          And the mocks are created

        Scenario: Example using fixture
          Given Mountebank is running
          And a mock exists at "/auth/user" it should return "200" with the fixture "json/user"
          And the mocks are created
      """
    When I run "behat features/mountebank-scenario.feature"
    Then it should pass with:
      """
      .................

      3 scenarios (3 passed)
      17 steps (17 passed)
      """

  Scenario: Testing Mountebank default mock
    Given a file named "features/mountebank-default.feature" with:
      """
      @billing @bicker @mountebank @annoy
      Feature: Testing Mountebank - Default Mock

        Scenario: Default mock enabled using config
          Given Mountebank is running
          And a mock exists at "/success" it should return "204"
          And the mocks are created
          When I make a "GET" request to "/error"
          Then the response status code should be "500"

        Scenario: Default mock enabled using step
          Given Mountebank is running
          And the default mock should return "404"
          And a mock exists at "/success" it should return "204"
          And the mocks are created
          When I make a "GET" request to "/error"
          Then the response status code should be "404"
      """
    When I run "behat features/mountebank-default.feature"
    Then it should pass with:
      """
      ...........

      2 scenarios (2 passed)
      11 steps (11 passed)
      """

  Scenario: Testing Mountebank - Clear Mocks
    Given a file named "features/example-feature-clear.feature" with:
      """
      @billing @bicker @mountebank @annoy
      Feature: Testing Mountebank - Clear Mocks

        Scenario: Default mock enabled after cleared
          Given Mountebank is running
          And a mock exists at "/success" it should return "204"
          And the mocks are created
          When I make a "GET" request to "/success"
          Then the response status code should be "204"
          Given all mocks are removed
          And the mocks are created
          When I make a "GET" request to "/success"
          Then the response status code should be "500"
      """
    When I run "behat features/example-feature-clear.feature"
    Then it should pass with:
      """
      .........

      1 scenario (1 passed)
      9 steps (9 passed)
      """

  Scenario: Testing Mountebank - Full Http Request
    Given a file named "features/example-feature-clear.feature" with:
      """
      @mountebank @fullhttp
      Feature: Testing Mountebank - Mock complete HTTP request

        Scenario: Mock successful HTTP request
          Given Mountebank is running
          And a mock exists at "/fullhttp" that represents the HTTP response:
          '''
          HTTP/1.1 200 OK
          Date: Mon, 27 Jul 2009 12:28:53 GMT
          Server: Apache/2.2.14 (Win32)
          Content-Length: 30
          Content-Type: text/html
          Hello World
          This is an example
          '''
          And the mocks are created
          When I make a "GET" request to "/fullhttp"
          Then the response status code should be "200"
          And the response body equals
          '''
          Hello World
          This is an example
          '''
          And the "Date" header property equals "Mon, 27 Jul 2009 12:28:53 GMT"
          And the "Server" header property equals "Apache/2.2.14 (Win32)"
          And the "Content-Length" header property equals "30"
          And the "Content-Type" header property equals "text/html"
      """
    When I run "behat features/example-feature-clear.feature"
    Then it should pass with:
      """
      ..........

      1 scenario (1 passed)
      10 steps (10 passed)
      """
