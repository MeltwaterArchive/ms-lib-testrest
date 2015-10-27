<?php
/**
 * This file is part of DataSift\TestRest project.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/ms-lib-testrest
 */

namespace DataSift\TestRest;

use \DataSift\TestRest\Exception;
use \Behat\Gherkin\Node\PyStringNode;
use \Behat\Gherkin\Node\TableNode;

/**
 * DataSift\TestRest\InputContext
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/ms-lib-testrest
 */
class InputContext extends \DataSift\TestRest\BaseContext
{
    /**
     * Assign a value to a header property.
     *
     * Example:
     *     Given that header property "Test" is "12345"
     *
     * @Given /^that header property "([^"]*)" is "([^\n]*)"$/
     */
    public function thatHeaderPropertyIs($propertyName, $propertyValue)
    {
        if (($propertyValue === 'null')) {
            return;
        }
        $this->reqHeaders[$propertyName] = $propertyValue;
    }

    /**
     * Assign a value to a property.
     *
     * Examples:
     *     Given that "property_name" is "12345"
     *     And that "data[0].name" is "alpha"
     *
     * @Given /^that "([^"]*)" is "([^\n]*)"$/
     */
    public function thatPropertyIs($propertyName, $propertyValue)
    {
        if (($propertyValue === 'null')) {
            return;
        }
        $val = $propertyValue;
        // explode property name
        $keys = array_reverse(explode('.', $propertyName));
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
        $this->restObj = (object)array_merge((array)$this->restObj, (array)$obj);
    }

    /**
     * Load several input values at once by reading the data from a JSON file.
     * NOTE: the data will be converted to an array of values.
     *
     * Example:
     *     Given that the properties are imported from the JSON file "/tmp/data.json"
     *
     * @Given /^that the properties are imported from the JSON file "([^"]*)"$/
     */
    public function thatThePropertiesAreImportedFromTheJsonFile($file)
    {
        if (!is_readable($file)) {
            throw new Exception('Unable to read the JSON file: '.$file);
        }
        $json = file_get_contents($file);
        $this->restObj = (object)array_merge((array)$this->restObj, json_decode($json, true));
    }
    

    /**
     * Overwrites the body payload with the content of the specified file.
     * For example, it can be used to send a raw JSON string.
     *
     * Example:
     *     Given that the body is imported from the file "/tmp/data.txt"
     *
     * @Given /^that the body is imported from the file "([^"]*)"$/
     */
    public function thatTheBodyIsImportedFromTheFile($file)
    {
        if (!is_readable($file)) {
            throw new Exception('Unable to read the text file: '.$file);
        }
        $this->restObj = file_get_contents($file);
    }

    /**
     * Overwrites the message body payload using the specified string.
     * For example, it can be used to send a binary, XML or JSON string.
     *
     * Examples:
     *
     *     Given that the body is
     *     """
     *     ajweriwerio328423947uhdiuqwdh2387ye372r23g7qed237g23e237e
     *     """
     *
     *     Given that the body is
     *     """
     *     <name>Hello</name>
     *     <email>name@example.com</email>
     *     """
     *
     *     Given that the body is
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @Given /^that the body is$/
     */
    public function thatTheBodyIs(PyStringNode $data)
    {
        $this->restObj = (string)$data;
    }

    /**
     * Overwrites the message body payload wih the provided JSON string
     * and set the Content-Type to "application/json".
     *
     * Example:
     *
     *     Given that the body is valid JSON
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @Given /^that the body is valid JSON$/
     */
    public function thatTheBodyIsValidJson(PyStringNode $data)
    {
        if (json_decode((string)$data) === null) {
            throw new Exception('The input is not a valid JSON.');
        }
        $this->thatHeaderPropertyIs('Content-Type', 'application/json');
        $this->thatTheBodyIs($data);
    }

    /**
     * Allows to specify properties using a tabular form.
     * The table is expected to have two columns:
     * the first contains the "property" name and the second the property "value".
     *
     * @param TableNode $table Input data table
     */
    protected function thatThePropertiesInTheTable(TableNode $table)
    {
        $hash = $table->getHash();
        foreach ($hash as $row) {
            $this->thatPropertyIs($row['property'], $row['value']);
        }
    }

    /**
     * Load several input values at once using JSON syntax.
     * NOTE: the data will be converted iternally to property-value items.
     *
     * @param PyStringNode $json input JSON
     */
    protected function thatThePropertiesInTheJson(PyStringNode $json)
    {
        $this->restObj = (object)array_merge((array)$this->restObj, json_decode($json, true));
    }

    /**
     * Allows to specify properties using a tabular (TABLE) form or a JSON.
     *
     * The TABLE form is recommended when the input data is a long list of property-value items.
     * The JSON format is recommended when multiple input properties are nested in a complex structure.
     *
     * In any case the input data provided will be internally converted in property-value items.
     *
     * The table is expected to have two columns:
     * the first contains the "property" name and the second the property "value".
     *
     * TABLE Example:
     *     Given that the properties in the "TABLE"
     *     | property    | value            |
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
        throw new Exception('Invalid type: '.$type.'; only "TABLE" and "JSON" are valid.');
    }

    /**
     * Perform a request to the specified end point.
     * NOTE: The properties to send with this request must be set before this entry.
     *
     * Example:
     *     When I make a "POST" request to "/my/api/entry/point"
     *     When I make a "GET" request to "/my/api/entry/point"
     *
     * @When /^I make a "(POST|PUT|PATCH|GET|HEAD|DELETE)" request to "([^"]*)"$/
     */
    public function iRequest($method, $pageUrl)
    {
        $this->restObjMethod = strtolower($method);
        $this->requestUrl = $this->getParameter('base_url').$pageUrl;
        $method = strtolower($this->restObjMethod);
        $headers = null;
        if (!empty($this->reqHeaders)) {
            $headers = (array)$this->reqHeaders;
        }
        $body = $this->restObj;
        if (!is_string($body)) {
            $body = (array)$this->restObj;
        }
        if (in_array($method, array('get', 'head', 'delete'))) {
            $url = $this->requestUrl;
            // add query properties (if any)
            if (!empty($body) && is_array($body)) {
                $url .= $this->getUrlQuerySeparator($url).http_build_query($body, '', '&');
            }
            $this->response = $this->client->$method($url)->send();
        } elseif (in_array($method, array('post', 'put', 'patch'))) {
            $this->response = $this->client->$method($this->requestUrl, $headers, $body)->send();
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
        if (parse_url($url, PHP_URL_QUERY) == null) {
            if (substr($url, -1) != '?') {
                return '?';
            }
        } else {
            // append the properties to the ones specified in the URL
            if (substr($url, -1) != '&') {
                return '&';
            }
        }
        return '';
    }
}
