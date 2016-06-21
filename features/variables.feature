Feature: Testing the variable store

  Background:
    Given a file named "behat.yml" with:
      """
      default:
          formatters:
              progress: ~
          extensions:
              DataSift\BehatExtension:
                  base_url: http://localhost:8080/

          suites:
              default:
                  contexts:
                    - 'DataSift\BehatExtension\Context\RestContext'
      """

  Scenario: Testing storing variables
    Given a file named "features/variables.feature" with:
      """
      Feature: Variable testing

        Scenario: Testing files
          Given that the request body is valid JSON
          '''
          {
            "alpha":"beta",
            "gamma":"delta",
            "count":3,
            "collection":["a","b","c"]
          }
          '''
          When I make a "POST" request to "/echo"
          Then the response status code should be "200"
          And the response is not empty
          And the response has a "alpha" property
          And the "alpha" property equals "beta"
          And save the "alpha" property into "alpha"
          And save "testing" to "beta"
          Given that the request body is valid JSON
          '''
          {
            "saved-variable-1":"<alpha>",
            "saved-variable-2":"<beta>"
          }
          '''
          When I make a "POST" request to "/echo"
          Then the response status code should be "200"
          And the response is not empty
          And the response has a "saved-variable-1" property
          And the response has a "saved-variable-2" property
          And the "saved-variable-1" property equals "beta"
          And the "saved-variable-2" property equals "testing"
          And the response body JSON equals
          '''
          {
            "saved-variable-1":"beta",
            "saved-variable-2":"testing"
          }
          '''
      """
    When I run "behat features/variables.feature"
    Then it should pass with:
      """
      ................

      1 scenario (1 passed)
      16 steps (16 passed)
      """
