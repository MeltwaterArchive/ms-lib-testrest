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
 * DataSift\TestRest\HeaderContext
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/ms-lib-testrest
 */
class HeaderContext extends \DataSift\TestRest\InputContext
{
    /**
     * Verify the value of the HTTP response status code.
     *
     * Example:
     *     Then the response status code should be "200"
     *
     * @param int $httpStatus Expected HTTP status code.
     *
     * @Then /^the response status code should be "(\d+)"$/
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
     * Check the value of an header property.
     *
     * Example:
     *     Then the "Connection" header property equals "close"
     *
     * @param string $propertyName  Name of the header property to check.
     * @param string $propertyValue Expected value of the header property.
     *
     * @Then /^the "([^"]+)" header property equals "([^\n]*)"$/
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
        $value = $this->response->getHeader($propertyName);
        $result = preg_match($pattern, $value);
        if (empty($result)) {
            throw new Exception(
                'The value of header \''.$propertyName.'\' is \''.$value
                .'\' and does not matches the pattern \''.$pattern.'\'!'."\n"
            );
        }
    }
}
