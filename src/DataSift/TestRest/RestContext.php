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

/**
 * DataSift\TestRest\RestContext
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/ms-lib-testrest
 */
class RestContext extends \DataSift\TestRest\BaseContext
{
    /**
     * @Given /^that "([^"]*)" is "([^\n]*)"$/
     *
     * Example:
     *     Given that "property.name" is "12345"
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
     * @Given /^that input JSON data is$/
     *
     * Example:
     *     Given that input JSON data is
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     */
    public function thatInputJsonDataIs(PyStringNode $json)
    {
        $this->restObj = (object)array_merge((array)$this->restObj, json_decode($json, true));
    }

    /**
     * @Given /^that input JSON data file is "([^"]*)"$/
     *
     * Example:
     *     Given that input JSON data file is "/tmp/data.json"
     */
    public function thatInputJsonDataFileIs($file)
    {
        if (!is_readable($file)) {
            throw new Exception('Unable to read the JSON file: '.$file);
        }
        $json = file_get_contents($file);
        $this->restObj = (object)array_merge((array)$this->restObj, json_decode($json, true));
    }

    /**
     * @When /^I make a "(POST|PUT|PATCH|GET|HEAD|DELETE)" request to "([^"]*)"$/
     *
     * Example:
     *     When I make a "POST" request to "/my/api/entry/point"
     *     When I make a "GET" request to "/my/api/entry/point"
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
        $body = (array)$this->restObj;
        if (in_array($method, array('get', 'head', 'delete'))) {
            $this->response = $this->client->$method($this->requestUrl.'?'.http_build_query($body))->send();
        } elseif (in_array($method, array('post', 'put', 'patch'))) {
            $this->response = $this->client->$method($this->requestUrl, $headers, $body)->send();
        }
    }

    /**
     * @Then /^the response status code should be "(\d+)"$/
     *
     * Example:
     *     Then the response status code should be "200"
     */
    public function theResponseStatusCodeShouldBe($httpStatus)
    {
        if ((string)$this->response->getStatusCode() !== (string)$httpStatus) {
            throw new Exception(
                'HTTP code does not match '.$httpStatus.
                ' (actual: '.$this->response->getStatusCode().')'
            );
        }
    }

    /**
     * @Then /^the "([^"]+)" header property equals "([^\n]*)"$/
     *
     * Example:
     *     Then the "Connection" header property equals "close"
     */
    public function theHeaderPropertyEquals($propertyName, $propertyValue)
    {
        $value = $this->response->getHeader($propertyName);
        if (($value === null) && ($propertyValue == 'null')) {
            return;
        }
        // compare values
        if ((string)$value !== (string)$propertyValue) {
            throw new Exception('Property value mismatch! (given: '.$propertyValue.', match: '.$value.')');
        }
    }

    /**
     * @Then /^the response is JSON$/
     *
     * Example:
     *     Then the response is JSON
     */
    public function getResponseData()
    {
        $data = json_decode($this->response->getBody(true));
        if (empty($data)) {
            throw new Exception('Response was not JSON:'."\n\n".$this->response->getBody(true));
        }
        return $data;
    }

    /**
     * @Then /^the response has a "([^"]*)" property$/
     *
     * Example:
     *     Then the response has a "field.name" property
     *
     * Get the object value given the property name in dot notation
     *
     * @param string   $property  Dot-separated property name
     * @param StdClass $obj       Object to process
     *
     * @return Object value
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
                    throw new Exception('Property \''.$property.'\' is not set!');
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
     * @Then /^the type of the "([^"]*)" property should be "([^"]+)"$/
     *
     * Examples:
     *     Then the type of the "field.name" property should be "string"
     *     Then the type of the "field.count" property should be "integer"
     */
    public function theTypeOfThePropertyShouldBe($propertyName, $type)
    {
        $value = $this->getObjectValue($propertyName);
        $valueType = gettype($value);
        if ($valueType !== $type) {
            throw new Exception(
                'Property \''.$propertyName.'\' is of type \''.$valueType
                .'\' and not \''.$type.'\'!'."\n"
            );
        }
    }

    /**
     * @Then /^the "([^"]+)" property equals "([^\n]*)"$/
     *
     * Example:
     *     Then the "success" property equals "true"
     */
    public function thePropertyEquals($propertyName, $propertyValue)
    {
        try {
            $value = $this->getObjectValue($propertyName);
        } catch (Exception $e) {
            if ($propertyValue == 'null') {
                return;
            }
            throw new Exception($e->getMessage());
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
            throw new Exception(
                'Property value mismatch! (given: '.$propertyValue.', match: '.json_encode($value).')'
            );
        }
        // compare values
        $value = (string)$value;
        if ($value !== $propertyValue) {
            throw new Exception('Property value mismatch! (given: '.$propertyValue.', match: '.$value.')');
        }
    }

    /**
     * @Then /^the "([^"]*)" property is an "(array|object)" with "(null|\d+)" item[s]?$/
     *
     * Examples:
     *     Then the "data" property is an "array" with "5" items
     *     Then the "data" property is an "object" with "10" items
     */
    public function thePropertyIsAnWithItems($propertyName, $type, $numitems)
    {
        try {
            $this->theTypeOfThePropertyShouldBe($propertyName, $type);
        } catch (Exception $e) {
            if ($numitems == 'null') {
                return;
            }
            throw new Exception($e->getMessage());
        }
        $value = count((array)$this->getObjectValue($propertyName));
        if ($value != $numitems) {
            throw new Exception('Property count mismatch! (given: '.$numitems.', match: '.$value.')');
        }
    }

    /**
     * @Then /^the length of the "([^"]*)" property should be "(\d+)"$/
     *
     * Example:
     *     Then the length of the "datetime" property should be "19"
     */
    public function theLengthOfThePropertyShouldBe($propertyName, $length)
    {
        $value_length = strlen($this->getObjectValue($propertyName));
        if ($value_length !== (int)$length) {
            throw new Exception(
                'The lenght of property \''.$propertyName.'\' is \''.$value_length
                .'\' and not \''.$length.'\'!'."\n"
            );
        }
    }
}
