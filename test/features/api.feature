Feature: Testing if the API is responding

Scenario: Test JSON data
    Given that the request body is valid JSON
    """
    {
        "alpha":"beta",
        "gamma":"delta",
        "count":3,
        "collection":["a","b","c"]
    }
    """
    When I make a "POST" request to "/"
    Then echo last response
    Then the response status code should be "200"
    Then the "success" property equals "true"
    
Scenario: Simple test that show all options
    Given that header property "Test" is "12345"
    And that "property[0].name" is "12345"
    # NOTE: The following loads data from a JSON file and converts it into property-value items
    Given that the properties in the "JSON"
    """
    {
        "alpha":"beta",
        "gamma":"delta",
        "count":3,
        "collection":["a","b","c"]
    }
    """
    # The following loads data from a JSON file and merge it with the existing one
    Given that the properties are imported from the JSON file "test/resources/data.json"
    When I make a "GET" request to "/"
    Then echo last response
    Then wait "1" second
    Then the response status code should be "200"
    Then the "Connection" header property equals "close"
    Then the response is JSON
    Then the response has a "success" property
    Then the type of the "success" property should be "boolean"
    Then the "success" property equals "true"
    Then the value of the "datetime" property should match the pattern "/^[0-9]{4}[\-][0-9]{2}[\-][0-9]{2} [0-9]{2}[:][0-9]{2}[:][0-9]{2}$/"
    Then the "data" property is an "object" with "10" items
    Then the "data.property" property is an "array" with "1" item
    Then the "data.property[0].name" property equals "12345"
    Then the "data.collection" property is an "array" with "3" items
    Then the length of the "data.gamma" property should be "5"
    Then the "data.alpha" property equals "beta"
    Then the "data.gamma" property equals "delta"
    Then the "data.count" property equals "3"
    Then the "data.string" property equals "one two"
    Then the "data.integer" property equals "123"
    Then the "data.float" property equals "1.2345"
    Then the "data.boolean" property equals "true"
    Then the "data.array" property is an "array" with "4" items

Scenario Outline: Test data table mode
    Given that "property.name" is "<name>"
    When I make a "GET" request to "/"
    Then the response status code should be "<code>"
    Then the response is JSON
    Then the "success" property equals "<success>"
    Then the "data.property.name" property equals "<name>"
    Examples:
        | name  | code | success |
        | alpha |  200 | true    |
        | bravo |  200 | true    |

Scenario: Test Raw data
    Given that the request body is
    """
    {
        "alpha":"beta",
        "gamma":"delta",
        "count":3,
        "collection":["a","b","c"]
    }
    """
    When I make a "POST" request to "/"
    Then the response status code should be "200"
    Then the "success" property equals "true"
    Then the response has a "raw" property

Scenario: Test RAW input file data
    # load RAW data from a file
    Given that the request body is imported from the file "test/resources/data.json"
    When I make a "POST" request to "/"
    Then the response status code should be "200"
    Then the "success" property equals "true"
    Then the response has a "raw" property

Scenario: Test input properties in tabular form
    Given that the properties in the "TABLE"
        | property    | value            |
        | name        | Nicola           |
        | email       | name@example.com |
    When I make a "GET" request to "/"
    Then echo last response
    Then the response status code should be "200"
    Then the "success" property equals "true"
    Then the "data.name" property equals "Nicola"
    Then the "data.email" property equals "name@example.com"
    Then the response has a "raw" property

