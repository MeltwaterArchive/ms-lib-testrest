Feature: Testing the File helper utils

  Background:
    Given a file named "behat.yml" with:
      """
      default:
          formatters:
              progress: ~
          extensions:
              DataSift\TestRestExtension:
                  base_url: http://localhost:8080/

          suites:
              default:
                  contexts:
                    - 'DataSift\TestRestExtension\Context\RestContext'
                    - 'DataSift\TestRestExtension\Context\FileContext'
      """

  Scenario: Testing config file is file is created and accessible
    Given a file named "features/file-accessible.feature" with:
      """
      Feature: File testing

        Scenario: Testing files
          Given a file named "conf.json" with:
          '''
          {
            "string":"one two",
            "integer":123,
            "float":1.2345,
            "boolean":true,
            "array":["a","b","c","d"]
          }
          '''
          And file "conf.json" should exist
          When I make a "POST" request to "/file"
          And echo last response
          Then the response status code should be "200"
          And the response is not empty
          And the response body contains the JSON data
          '''
          {
            "string":"one two",
            "integer":123,
            "float":1.2345,
            "boolean":true,
            "array":["a","b","c","d"]
          }
          '''
      """
    When I run "behat features/file-accessible.feature"
    Then it should pass with:
      """
      .......

      1 scenario (1 passed)
      7 steps (7 passed)
      """