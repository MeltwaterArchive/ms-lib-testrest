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
     * Example:
     *     Given that "property.name" is "12345"
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
     * Load several input values at once using JSON syntax.
     * NOTE: the data will be converted to an array of values.
     *
     * Example:
     *     Given that input JSON data is
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @Given /^that input JSON data is$/
     */
    public function thatInputJsonDataIs(PyStringNode $json)
    {
        $this->restObj = (object)array_merge((array)$this->restObj, json_decode($json, true));
    }

    /**
     * Load several input values at once by reading the data from a JSON file.
     * NOTE: the data will be converted to an array of values.
     *
     * Example:
     *     Given that input JSON data file is "/tmp/data.json"
     *
     * @Given /^that input JSON data file is "([^"]*)"$/
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
     * Overwrites the message body payload using the specified string.
     * For example, it can be used to send a raw JSON string.
     *
     * Example:
     *     Given that input RAW data is
     *     """
     *     {
     *          "field":"value",
     *          "count":1
     *     }
     *     """
     *
     * @Given /^that input RAW data is$/
     */
    public function thatInputRawDataIs(PyStringNode $data)
    {
        $this->restObj = (string)$data;
    }

    /**
     * Overwrites the body payload with the content of the specified file.
     * For example, it can be used to send a raw JSON string.
     *
     * Example:
     *     Given that input RAW data file is "/tmp/data.txt"
     *
     * @Given /^that input RAW data file is "([^"]*)"$/
     */
    public function thatInputRawDataFileIs($file)
    {
        if (!is_readable($file)) {
            throw new Exception('Unable to read the text file: '.$file);
        }
        $this->restObj = file_get_contents($file);
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
                if (parse_url($url, PHP_URL_QUERY) == null) {
                    if (substr($url, -1) != '?') {
                        $url .= '?';
                    }
                } else {
                    // append the properties to the ones specified in the URL
                    if (substr($url, -1) != '&') {
                        $url .= '&';
                    }
                }
                $url .= http_build_query($body, '', '&');
            }
            $this->response = $this->client->$method($url)->send();
        } elseif (in_array($method, array('post', 'put', 'patch'))) {
            $this->response = $this->client->$method($this->requestUrl, $headers, $body)->send();
        }
    }
}
