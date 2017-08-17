<?php

namespace DataSift\BehatExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_Assert as Assertions;

class RestContext extends File implements ApiClientAwareContext, FileAwareContext
{
    /**
     * @var string
     */
    protected $authorization;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var \GuzzleHttp\Message\RequestInterface
     */
    protected $request;

    /**
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * Object containing the data to exchange
     *
     * @var \stdClass
     */
    protected $restObj = null;

    protected $placeHolders = array();

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /* ----------------------------------------------------------------------------- */
    /* ----------------------------------HELPERS------------------------------------ */
    /* ----------------------------------------------------------------------------- */

    /**
     * Delays the program execution for the given number of seconds.
     *
     * Examples:
     *     Then wait "1" second
     *     Then wait "3" seconds
     *
     * @param int $delay Halt time in seconds.
     *
     * @Then /^wait "(\d+)" second[s]?$/
     */
    public function waitSeconds($delay)
    {
        sleep($delay);
    }

    /**
     * Print the last raw response.
     *
     * Example:
     *     Then echo last response
     *
     * @Then /^echo last response$/
     * @Then print response
     */
    public function printResponse()
    {
        $request = $this->request;
        $response = $this->response;

        echo sprintf(
            "\033[36m%s => %s\033[0m\n\n\033[36mHTTP/%s %s %s\033[0m\n",
            $request->getMethod(),
            (string)$request->getUri(),
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        foreach($response->getHeaders() as $key => $value) {
            echo sprintf("\033[36m%s: %s\033[0m\n", $key, implode('; ', $value));
        }

        echo sprintf(
            "\n\033[36m%s\033[0m",
            $response->getBody()
        );
    }

    /* ----------------------------------------------------------------------------- */
    /* ------------------------------INPUT (headers)-------------------------------- */
    /* ----------------------------------------------------------------------------- */

    /**
     * Adds Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
     * @Given /^my username is "([^"]*)" and my API key is "([^"]*)"$/
     */
    public function myUsernameIsAndMyApiKeyIs($username, $password)
    {
        $this->removeHeader('Authorization');
        $this->authorization = $username . ':' . $password;
        $this->addHeader('Authorization', $this->authorization);
    }

    /**
     * Assign a value to a request header property.
     *
     * Example:
     *     Given that header property "Test" is "12345"
     *
     * @param string $name  Name of the header property to set
     * @param string $value Value of the header property
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     * @Given /^that header property "([^"]*)" is "([^\n]*)"$/
     */
    public function thatHeaderPropertyIs($name, $value)
    {
        if (($value === 'null')) {
            return;
        }
        $processedValue = $this->processForVariables($value);
        $this->addHeader($name, $processedValue);
    }

    /* ----------------------------------------------------------------------------- */
    /* -----------------------------INPUT (payload)--------------------------------- */
    /* ----------------------------------------------------------------------------- */

    /**
     * Overwrites the message body payload wih the provided JSON string
     * and set the Content-Type to "application/json".
     *
     * Example:
     *
     *     Given that the request body is valid JSON
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @param PyStringNode $data Request body content in JSON format.
     *
     * @Given /^that the request body is valid JSON$/
     */
    public function thatTheRequestBodyIsValidJson(PyStringNode $data)
    {
        $body = $this->processForVariables((string)$data);
        Assertions::assertNotNull(json_decode((string)$body), 'The input is not a valid JSON.');
        $this->thatHeaderPropertyIs('Content-Type', 'application/json');
        $this->thatTheRequestBodyIs($data);
    }

    /**
     * Assign a value to a property.
     *
     * Examples:
     *     Given that "property_name" is "12345"
     *     And that "data[0].name" is "alpha"
     *
     * @param string $name  Name of the property to set
     * @param string $value Value of the property
     *
     * @Given /^that "([^"]*)" is "([^\n]*)"$/
     */
    public function thatPropertyIs($name, $value)
    {
        if ($value === 'null') {
            return;
        }
        $val = $value;
        // explode property name
        $keys = array_reverse(explode('.', $name));
        foreach ($keys as $key) {
            // extract the array index (if any)
            $kdx = explode('[', $key);
            unset($idx);
            if (!empty($kdx[1])) {
                $key = $kdx[0];
                $idx = substr($kdx[1], 0, -1);
            }
            if (isset($idx)) {
                $val = array($idx => $val);
            }
            $obj = new \stdClass();
            $obj->$key = $val;
            $val = $obj;
        }

        $this->restObj = (object)array_merge((array) $this->restObj, (array)$obj);
    }

    /**
     * Overwrites the message body payload using the specified string.
     * For example, it can be used to send a binary, XML or JSON string.
     *
     * Examples:
     *
     *     Given that the request body is
     *     """
     *     ajweriwerio328423947uhdiuqwdh2387ye372r23g7qed237g23e237e
     *     """
     *
     *     Given that the request body is
     *     """
     *     <name>Hello</name>
     *     <email>name@example.com</email>
     *     """
     *
     *     Given that the request body is
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @param PyStringNode $data Request body content.
     *
     * @Given /^that the request body is$/
     */
    public function thatTheRequestBodyIs(PyStringNode $data)
    {
        $this->restObj = (string) $data;
    }

    /**
     * Load several input property values at once by reading the data from a JSON file.
     * NOTE: the data will be internally converted to property-value items.
     *
     * Example:
     *     Given that the properties are imported from the JSON file "/tmp/data.json"
     *
     * @param string $file Name and path of the file containing the source data in JSON format.
     *
     * @Given /^that the properties are imported from the JSON file "([^"]*)"$/
     */
    public function thatThePropertiesAreImportedFromTheJsonFile($file)
    {
        if (!is_readable($file)) {
            throw new \Exception('Unable to read the JSON file: '.$file);
        }
        $json = file_get_contents($file);
        $this->thatThePropertiesInTheJson($json);
    }

    /**
     * Overwrites the body payload with the content of the specified file.
     * For example, it can be used to send a binary, XML or JSON string.
     *
     * Example:
     *     Given that the request body is imported from the file "/tmp/data.txt"
     *
     * @param string $file Name and path of the file containing the source data.
     *
     * @Given /^that the request body is imported from the file "([^"]*)"$/
     */
    public function thatTheRequestBodyIsImportedFromTheFile($file)
    {
        if (!is_readable($file)) {
            throw new \Exception('Unable to read the text file: '.$file);
        }
        $this->restObj = file_get_contents($file);
    }

    /**
     * Allows to specify properties using a tabular form.
     * The table is expected to have two columns:
     * the first column contains the property name and the second the property value.
     *
     * @param TableNode $table Input data table
     */
    protected function thatThePropertiesInTheTable(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->thatPropertyIs($row[0], $row[1]);
        }
    }

    /**
     * Load several input values at once using JSON syntax.
     * NOTE: the data will be converted iternally to property-value items.
     *
     * @param string $json JSON string containing the property values.
     */
    protected function thatThePropertiesInTheJson($json)
    {
        $this->restObj = (object)array_merge((array)$this->restObj, json_decode($json, true));
    }

    /**
     * Allows to specify properties using a tabular form (TABLE) or JSON.
     *
     * The TABLE form is recommended when the input data is a long list of property-value items.
     * The JSON format is recommended when multiple input properties are nested in a complex structure.
     *
     * In any case the input data provided will be internally converted in property-value items.
     *
     * The table is expected to have two columns:
     * the first column contains the property name and the second the property value.
     *
     * TABLE Example:
     *     Given that the properties in the "TABLE"
     *     | name        | Nicola           |
     *     | email       | name@example.com |
     *
     * JSON Example:
     *     Given that the properties in the "JSON"
     *     """
     *     {
     *          "field":"value",
     *          "data": {
     *              "codes": [
     *                  "alpha",
     *                  "beta",
     *                  "gamma"
     *              ]
     *          }
     *     }
     *     """
     *
     * @param string $type Type of input data (TABLE or JSON).
     * @param string $data String containing the data to be parsed.
     *
     * @Given /^that the properties in the "(TABLE|JSON)"$/
     */
    public function thatThePropertiesInThe($type, $data)
    {
        if (($type == 'TABLE') && ($data instanceof TableNode)) {
            return $this->thatThePropertiesInTheTable($data);
        }
        if (($type == 'JSON') && ($data instanceof PyStringNode)) {
            return $this->thatThePropertiesInTheJson($data);
        }
        throw new \Exception('Invalid type: '.$type.'; only "TABLE" and "JSON" are valid.');
    }

    /* ----------------------------------------------------------------------------- */
    /* -----------------------------------SEND-------------------------------------- */
    /* ----------------------------------------------------------------------------- */

    /**
     * Perform a request to the specified end point.
     * NOTE: The properties to send with this request must be set before this entry.
     *
     * Example:
     *     When I make a "POST" request to "/my/api/entry/point"
     *     When I make a "GET" request to "/my/api/entry/point"
     *
     * @param string $method HTTP method (POST|PUT|PATCH|GET|HEAD|DELETE).
     * @param string $url    URL of the RESTful service to test.
     *
     * @When /^I make a "(POST|PUT|PATCH|GET|HEAD|DELETE)" request to "([^"]*)"$/
     */
    public function iRequest($method, $url)
    {
        $url = $this->prepareUrl($url);
        $method = strtolower($method);

        $body = $this->restObj;
        if (!is_string($body)) {
            $body = (array)$this->restObj;
        }

        // Reset the restObj, so we can send another request in the same scenario
        $this->restObj = null;

        if (in_array($method, array('get', 'head', 'delete'))) {
            // add query properties (if any)
            if (!empty($body) && is_array($body)) {
                $url .= $this->getUrlQuerySeparator($url).http_build_query($body, '', '&');
            }

            $this->request = $this->createRequest(
                $method,
                $url,
                array(
                    'headers' => $this->getHeaders()
                )
            );
        } elseif (in_array($method, array('post', 'put', 'patch'))) {
            if (is_string($body)) {
                $body = $this->processForVariables($body);
            }

            $this->request = $this->createRequest(
                $method,
                $url,
                array(
                    'headers' => $this->getHeaders(),
                    'body' => $body,
                )
            );
        } else {
            throw new \Exception('Method was not one of the allowed values');
        }
        $this->sendRequest();
    }

    /**
     * Create a HTTP(S) request based on the method, url, headers and body provided
     *
     * @param string $method  HTTP method (POST|PUT|PATCH|GET|HEAD|DELETE)
     * @param string $url     URL of the RESTful service to test
     * @param array $options  headers and body
     *
     * @return Request
     */
    protected function createRequest($method, $url, $options)
    {
        $headers = array_key_exists('headers', $options) ? $options['headers'] : [];
        $body = array_key_exists('body', $options) && !empty($options['body']) ? $options['body'] : null;
        return new Request($method, $url, $headers, $body);
    }

    /**
     * Process stored variables and insert them to the JSON string
     *
     * @param $body
     *
     * @return mixed
     */
    protected function processForVariables($body)
    {
        $variables = $this->openJSONFile('variables.json');
        foreach ($variables as $key => $value) {
            $body = preg_replace('/<' . $key . '>/i', $value, $body);
        }

        return $body;
    }

    /* ----------------------------------------------------------------------------- */
    /* ----------------------------RESPONSE (headers)------------------------------- */
    /* ----------------------------------------------------------------------------- */

    /**
     * Check the value of an header property.
     *
     * Example:
     *     Then the "Connection" header property equals "close"
     *
     * @param string $key      Name of the header property to check.
     * @param string $expected Expected value of the header property.
     *
     * @Then /^the "([^"]+)" header property equals "([^\n]*)"$/
     */
    public function theHeaderPropertyEquals($key, $expected)
    {
        $actual = $this->response->getHeaderLine($key);
        if (($actual === null) && ($expected == 'null')) {
            return;
        }

        Assertions::assertSame($expected, $actual);
    }

    /**
     * Check if the value of the specified header property matches the defined regular expression pattern
     *
     * Example:
     *     Then the value of the "Location" header property matches the pattern "^\/api\/[1-9][0-9]*$"
     *
     * @param string $propertyName Name of the header property to check.
     * @param string $pattern      Regular expression pattern to match
     *
     * @Then /^the value of the "([^"]*)" header property matches the pattern "([^\n]*)"$/
     */
    public function theValueOfTheHeaderPropertyMatchesThePattern($propertyName, $pattern)
    {
        $value = $this->response->getHeaderLine($propertyName);
        Assertions::assertRegExp(
            $pattern,
            $value,
            'The value of header \''.$propertyName.'\' is \''.$value
            .'\' and does not matches the pattern \''.$pattern.'\'!'."\n"
        );
    }

    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be "([^"]*)"$/
     * @Then /^the response status code should be "([^"]*)"$/
     */
    public function theResponseCodeShouldBe($code)
    {
        $expected = $code;
        $actual = $this->response->getStatusCode();
        $pattern = preg_replace('/x/i', '[0-9]', "{$expected}");
        Assertions::assertRegExp("/^{$pattern}$/", "{$actual}");
    }

    /* ----------------------------------------------------------------------------- */
    /* --------------------------------VARIABLES------------------------------------ */
    /* ----------------------------------------------------------------------------- */

    /**
     * Save property to variable.
     *
     * @Then /^save the "([^"]*)" property into "([^"]*)"$/
     */
    public function saveThePropertyInto($propertyName, $variable)
    {
        try {
            $value = $this->getObjectValue($propertyName);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $variables = $this->openJSONFile('variables.json');
        $variables[$variable] = $value;
        $this->saveJSONFile('variables.json', $variables);
    }

    /**
     * Save value to variable.
     *
     * @Then /^save "((?:\\.|[^\\"])*)" to "([^"]*)"$/
     */
    public function saveTo($value, $variable)
    {
        $variables = $this->openJSONFile('variables.json');
        $processedValue = $this->processForVariables($value);
        $variables[$variable] = $processedValue;
        $this->saveJSONFile('variables.json', $variables);
    }

    /**
     * Print the stored variables
     *
     * Example:
     *     Then echo stored variables
     *
     * @Then /^echo stored variables$/
     * @Then print variables
     */
    public function printVariables()
    {
        $variables = $this->openJSONFile('variables.json');

        $first = true;
        foreach($variables as $key => $value) {
            echo sprintf((!$first ? "\n" : "") . "\033[36m%s: %s\033[0m", $key, $value);
            $first = false;
        }
    }

    /**
     * Unset a variable.
     *
     * @Then /^(?:I )?unset "([^"]*)"$/
     */
    public function unsetValue($variable)
    {
        $variables = $this->openJSONFile('variables.json');
        unset($variables[$variable]);
        $this->saveJSONFile('variables.json', $variables);
    }

    /* ----------------------------------------------------------------------------- */
    /* ----------------------------RESPONSE (payload)------------------------------- */
    /* ----------------------------------------------------------------------------- */

    /**
     * Verify if the response is in valid JSON format.
     *
     * Example:
     *     Then the response is JSON
     *
     * @Then /^the response is JSON$/
     */
    public function getResponseData()
    {
        $data = json_decode($this->response->getBody());
        Assertions::assertNotEmpty($data, 'Response was not JSON:'."\n\n".$this->response->getBody());
        return $data;
    }

    /**
     * Checks that response body contains specific text.
     *
     * Examples:
     *     Then the the response body equals
     *     """
     *     name@example.com
     *     """
     *
     * @param PyStringNode $value Expected response body content.
     *
     * @Then /^the response body equals$/
     */
    public function theResponseBodyEquals(PyStringNode $value)
    {
        $actual = (string) $this->response->getBody();
        $pattern = '/' . trim($value->getRaw()) . '/';
        $pattern = $this->processForVariables($pattern);
        Assertions::assertRegExp($pattern, $actual, 'Response body value mismatch! (given: '.$value.', match: '.$actual.')');
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should contain "([^"]*)"$/
     */
    public function theResponseShouldContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/i';
        $actual = (string) $this->response->getBody();
        Assertions::assertRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/';
        $actual = (string) $this->response->getBody();
        Assertions::assertNotRegExp($expectedRegexp, $actual);
    }

    /**
     * Check if the response body content contains the specified JSON data.
     *
     * Examples:
     *     Then the response body contains the JSON data
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @param PyStringNode $jsonString JSON string containing the data expected in the response body.
     *
     * @throws \RuntimeException
     *
     * @Then /^the response body contains the JSON data$/
     * @Then /^(?:the )?response should contain json:$/
     *
     * @return array Array containing the actual returned data and the expected one.
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $substituted = $this->replacePlaceHolder($jsonString->getRaw());
        $substituted = $this->processForVariables($substituted);

        $etalon = json_decode($substituted, true);
        $actual = json_decode((string)$this->response->getBody(), true);
        if (null === $etalon) {
            throw new \RuntimeException(
                "Can not convert etalon to json:\n" . $substituted
            );
        }

        Assertions::assertGreaterThanOrEqual(count($etalon), count($actual));
        foreach ($etalon as $key => $needle) {
            Assertions::assertArrayHasKey($key, $actual);
            if ($etalon[$key] != '*') {
                Assertions::assertEquals($etalon[$key], $actual[$key]);
            }
        }

        return array($actual, $etalon);
    }

    /**
     * Check if the response body JSON structure and contents exactly matches the provided one.
     *
     * Examples:
     *     Then the response body JSON equals
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @param PyStringNode $value JSON string containing the data expected in the response body.
     *
     * @Then /^the response body JSON equals$/
     */
    public function theResponseBodyJsonEquals(PyStringNode $value)
    {
        list($data, $value) = $this->theResponseShouldContainJson($value);
        $diff = $this->getArrayDiff($data, $value);
        Assertions::assertEmpty($diff, 'Response body value mismatch! Extra item(s):'."\n".print_r($diff, true));
    }

    /**
     * Check if the response has the specified property.
     * Get the object value given the property name in dot notation.
     *
     * Example:
     *     Then the response has a "field.name" property
     *
     * @param string   $property  Property name in dot-separated format.
     * @param StdClass $obj       Object to process.
     *
     * @return Object value
     *
     * @Then /^the response has a "([^"]*)" property$/
     */
    public function getObjectValue($property, $obj = null)
    {
        if ($obj === null) {
            $obj = $this->getResponseData();
        }
        // explode property name
        $keys = explode('.', $property);
        foreach ($keys as $key) {
            // extract the array index (if any)
            $kdx = explode('[', $key);
            unset($idx);
            if (!empty($kdx[1])) {
                $key = $kdx[0];
                $idx = substr($kdx[1], 0, -1);
            }
            if (!isset($obj->$key)) {
                $key = '"'.$key.'"';
                if (!isset($obj->$key)) {
                    throw new \RuntimeException('Property \''.$property.'\' is not set!');
                }
            }
            $obj = $obj->$key;
            if (isset($idx)) {
                $obj = $obj[$idx];
            }
        }
        return $obj;
    }

    /**
     * Check the value of the specified property.
     * NOTE: the dot notation is supported for the property name (e.g. parent.child[0].value).
     *
     * Examples:
     *     Then the "success" property equals "true"
     *     Then the "database[0].hostname" property equals "127.0.0.1"
     *
     * @param string $propertyName  Name of the property to check.
     * @param string $propertyValue Expected value of the property.
     *
     * @Then /^the "([^"]+)" property equals "([^\n]*)"$/
     */
    public function thePropertyEquals($propertyName, $propertyValue)
    {
        try {
            $value = $this->getObjectValue($propertyName);
        } catch (\Exception $e) {
            if ($propertyValue == 'null') {
                return;
            }
            throw new \Exception($e->getMessage());
        }
        // cast boolean values
        if (($propertyValue === 'true') || ($propertyValue === 'false')) {
            $value = ((bool)$value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $apv = json_decode($propertyValue, true);
            $asv = json_decode(json_encode($value), true);
            // compare arrays
            if ($apv == $asv) {
                return;
            }
            throw new \Exception(
                'Property value mismatch! (given: '.$propertyValue.', match: '.json_encode($value).')'
            );
        }
        // compare values
        $value = (string)$value;
        if ($value !== $propertyValue) {
            throw new \Exception('Property value mismatch! (given: '.$propertyValue.', match: '.$value.')');
        }
    }

    /**
     * Check if the provided pattern matches the response body string.
     *
     * Example:
     *     Then the response body matches the pattern "/[a-z]+@example\.com/"
     *
     * @param string $pattern Regular expression pattern to search.
     *
     * @Then /^the response body matches the pattern "([^\n]*)"$/
     */
    public function theResponseBodyMatchesThePattern($pattern)
    {
        $value = trim($this->response->getBody());
        $result = preg_match($pattern, $value);
        Assertions::assertNotEmpty($result, 'The response body does not matches the pattern \''.$pattern.'\'!'."\n");
    }

    /**
     * Check the type of the specified property.
     *
     * Examples:
     *     Then the type of the "field.name" property should be "string"
     *     Then the type of the "field.count" property should be "integer"
     *
     * @param string $name  Name of the property to check.
     * @param string $type  Expected type of the property (boolean, integer, double, float, string, array)
     *
     * @Then /^the type of the "([^"]*)" property should be "([^"]+)"$/
     */
    public function theTypeOfThePropertyShouldBe($name, $type)
    {
        $value = $this->getObjectValue($name);
        $actual = gettype($value);
        Assertions::assertEquals($type, $actual, "Property '{$name}' is of type '{$actual}' and not '{$type}'!\n");
    }

    /**
     * Check if the specified property is an array or object with the indicated number of items.
     *
     * Examples:
     *     Then the "data" property is an "array" with "5" items
     *     Then the "data" property is an "object" with "10" items
     *
     * @param string $name     Name of the property to check.
     * @param string $type     Type of property ("array" or "object").
     * @param string $numitems Expected number of elements in the array or object.
     *
     * @Then /^the "([^"]*)" property is an "(array|object)" with "(null|\d+)" item[s]?$/
     */
    public function thePropertyIsAnWithItems($name, $type, $numitems)
    {
        $this->theTypeOfThePropertyShouldBe($name, $type);
        if ($numitems == 'null') {
            return;
        }
        Assertions::assertCount(
            (int) $numitems,
            (array) $this->getObjectValue($name),
            'Property count mismatch! (given: '.$numitems.', match: '.count((array) $this->getObjectValue($name)).')'
        );
    }

    /**
     * Check the length of the property value.
     *
     * Example:
     *     Then the length of the "datetime" property should be "19"
     *
     * @param string $name   Name of the property to check.
     * @param string $length Expected string length of the property.
     *
     * @Then /^the length of the "([^"]*)" property should be "(\d+)"$/
     */
    public function theLengthOfThePropertyShouldBe($name, $length)
    {
        $actualLength = strlen($this->getObjectValue($name));
        Assertions::assertEquals($length, $actualLength, "The length of property '{$name}' is '{$actualLength}' and not '{$length}'!\n");
    }

    /**
     * Check if the value of the specified property matches the defined regular expression pattern
     *
     * Example:
     *     Then the value of the "datetime" property matches the pattern
     *     "/^[0-9]{4}[\-][0-9]{2}[\-][0-9]{2} [0-9]{2}[:][0-9]{2}[:][0-9]{2}$/"
     *
     * @param string $name    Name of the property to check.
     * @param string $pattern Expected regular expression pattern of the property.
     *
     * @Then /^the value of the "([^"]*)" property matches the pattern "([^\n]*)"$/
     */
    public function theValueOfThePropertyMatchesThePattern($name, $pattern)
    {
        $value = (string)$this->getObjectValue($name);
        Assertions::assertRegExp(
            $pattern,
            $value,
            "The value of property '{$name}' is '{$value}' and does not matches the pattern '{$pattern}'!\n"
        );
    }

    /**
     * Check if the response body is empty.
     *
     * Example:
     *     Then the response is empty
     *
     * @Then /^the response is empty$/
     */
    public function theResponseIsEmpty()
    {
        $data = trim($this->response->getBody());
        Assertions::assertEmpty($data, "Response body is not empty! (match: {$data})");
    }

    /**
     * Check if the response body is not empty.
     *
     * Example:
     *     Then the response is not empty
     *
     * @Then /^the response is not empty$/
     */
    public function theResponseIsNotEmpty()
    {
        $data = trim($this->response->getBody());
        Assertions::assertNotEmpty($data, 'Response body is empty!');
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl($url)
    {
        return $this->processForVariables(ltrim($this->replacePlaceHolder($url), '/'));
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key   token name
     * @param string $value replace value
     */
    public function setPlaceHolder($key, $value)
    {
        $this->placeHolders[$key] = $value;
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Returns headers, that will be used to send requests.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header
     *
     * @param string $name
     * @param string $value
     */
    protected function addHeader($name, $value)
    {
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }

            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        if (array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);
        }
    }

    protected function sendRequest()
    {
        try {
            $this->response = $this->getClient()->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }

    /**
     * Get the first URL query separator ('?' or '&')
     *
     * @param string $url URL to parse
     *
     * @return string
     */
    protected function getUrlQuerySeparator($url)
    {
        $sep = '';
        if (parse_url($url, PHP_URL_QUERY) == null) {
            if (substr($url, -1) != '?') {
                $sep = '?';
            }
        } else {
            // append the properties to the ones specified in the URL
            if (substr($url, -1) != '&') {
                $sep = '&';
            }
        }
        return $sep;
    }

    protected function getClient()
    {
        if (null === $this->client) {
            throw new \RuntimeException('Client has not been set');
        }

        return $this->client;
    }

    /**
     * Returns the difference of two arrays
     *
     * @param array $arr1 The array to compare from.
     * @param array $arr2 The array to compare against.
     *
     * @return array Returns an array containing all the entries from $arr1 that are not present in $arr2.
     */
    protected function getArrayDiff(array $arr1, array $arr2)
    {
        $diff = array();
        foreach ($arr1 as $key => $val) {
            if (array_key_exists($key, $arr2)) {
                if (is_array($val)) {
                    $tmpdiff = $this->getArrayDiff($val, $arr2[$key]);
                    if (!empty($tmpdiff)) {
                        $diff[$key] = $tmpdiff;
                    }
                } elseif ($arr2[$key] !== $val) {
                    $diff[$key] = $val;
                }
            } elseif (!is_int($key) || !in_array($val, $arr2)) {
                $diff[$key] = $val;
            }
        }
        return $diff;
    }
}
