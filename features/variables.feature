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
          And save "two variables <alpha> and <beta> combined" to "epsilon"
          And save "text \"string\" in escaped double quotes" to "zeta"
          And save "variable \"<alpha>\" in escaped double quotes" to "eta"
          Given that the request body is valid JSON
          '''
          {
            "saved-variable-1":"<alpha>",
            "saved-variable-2":"<beta>",
            "saved-variable-3":"<epsilon>",
            "saved-variable-4":"<zeta>",
            "saved-variable-5":"<eta>"
          }
          '''
          When I make a "POST" request to "/echo"
          Then the response status code should be "200"
          And the response is not empty
          And the response has a "saved-variable-1" property
          And the response has a "saved-variable-2" property
          And the "saved-variable-1" property equals "beta"
          And the "saved-variable-2" property equals "testing"
          And the "saved-variable-3" property equals "two variables beta and testing combined"
          And the "saved-variable-4" property equals "text "string" in escaped double quotes"
          And the "saved-variable-5" property equals "variable "beta" in escaped double quotes"
          '''
          {
            "saved-variable-1":"<alpha>",
            "saved-variable-2":"<beta>",
            "saved-variable-3":"<epsilon>",
            "saved-variable-4":"<zeta>",
            "saved-variable-5":"<eta>"
          }
          '''
          When I make a "POST" request to "/echo?test=<alpha>"
          And the "query.test" property equals "beta"
          And echo stored variables
          And I unset "alpha"
          And echo stored variables
      """
    When I run "behat features/variables.feature"
    Then it should pass with:
      """
      .......................

      1 scenario (1 passed)
      27 steps (27 passed)
      """
