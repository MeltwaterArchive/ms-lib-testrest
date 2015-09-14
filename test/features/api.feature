Feature: Testing if the API is responding

Scenario: Simple test
    Given that "property.name" is "12345"
    Given that input JSON data is
    """
    {
        "alpha":"beta",
        "gamma":"delta",
        "count":3,
        "collection":["a","b","c"]
    }
    """
    When I make a "GET" request to "/"
    Then the response status code should be "200"
    Then the response is JSON
    Then the response has a "success" property
    Then the type of the "success" property should be "boolean"
    Then the "success" property equals "true"
    Then the "data" property is an "object" with "5" items
    Then the "data.collection" property is an "array" with "3" items
    Then the length of the "data.gamma" property should be "5"
    Then the "data.alpha" property equals "beta"
    Then the "data.gamma" property equals "delta"
    Then the "data.count" property equals "3"
    #Then wait "1" second
    #Then echo last response
