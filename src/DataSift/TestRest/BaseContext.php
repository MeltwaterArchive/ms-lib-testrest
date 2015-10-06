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
use \Behat\Behat\Context\BehatContext;

/**
 * DataSift\TestRest\BaseContext
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/ms-lib-testrest
 */
class BaseContext extends BehatContext
{
    /**
     * Context parameters defined in behat.yml (default.context.parameters...)
     *
     * @var array
     */
    protected static $parameters = array();

    /**
     * Guzzle Client used fot HTTP requests
     *
     * @var \Guzzle\Service\Client
     */
    protected $client = null;

    /**
     * Object containing the data to exchange
     *
     * @var stdClass
     */
    protected $restObj = null;

    /**
     * Array containing the header data to send
     *
     * @var array
     */
    protected $reqHeaders = array();

    /**
     * HTTP method (get, head, delete, post, put, patch)
     *
     * @var string
     */
    protected $restObjMethod = 'get';

    /**
     * Response object
     *
     * @var stdClass
     */
    protected $response = null;

    /**
     * The URL of the request
     *
     * @var string
     */
    protected $requestUrl = null;

    /**
     * Initializes the BeHat context for every scenario
     *
     * @param array $parameters Context parameters defined in behat.yml (default.context.parameters...)
     */
    public function __construct(array $parameters)
    {
        $this->client = new \Guzzle\Service\Client();
        $this->client->setDefaultOption('exceptions', false); // disable exceptions: we want to test error responses
        self::$parameters = $parameters;
        $this->restObj = new \stdClass();
    }

    /**
     * Get the value of the specified parameter
     * The context parameters are defined in behat.yml (default.context.parameters...)
     *
     * @param string $name Parameter name
     *
     * @return mixed Parameter value
     */
    public function getParameter($name)
    {
        if (empty(self::$parameters)) {
            throw new Exception('Context Parameters not loaded!');
        }
        return ((isset(self::$parameters[$name])) ? self::$parameters[$name] : null);
    }

    /**
     * @BeforeFeature
     *
     * Setup the database before every feature
     * The database settings are defined in behat.yml
     */
    public static function setupEnvironment()
    {
        // clean the APC cache (if any)
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
        }
        
        if (empty(self::$parameters['db'])) {
            // no database defined
            return;
        }

        // load the SQL queries to process
        $sql = "\n".file_get_contents(__DIR__ . self::$parameters['db']['sql_schema'])
            ."\n".file_get_contents(__DIR__ . self::$parameters['db']['sql_data'])."\n";

        // split sql string into single line SQL statements
        $sql = str_replace("\r", '', $sql);                         // remove CR
        $sql = preg_replace("/\/\*([^\*]*)\*\//si", ' ', $sql);     // remove comments (/* ... */)
        $sql = preg_replace("/\n([\s]*)\#([^\n]*)/si", '', $sql);   // remove comments (lines starting with '#')
        $sql = preg_replace("/\n([\s]*)\-\-([^\n]*)/si", '', $sql); // remove comments (lines starting with '--')
        $sql = preg_replace("/;([\s]*)\n/si", ";\r", $sql);         // mark valid new lines
        $sql = str_replace("\n", ' ', $sql);                        // replace new lines with a space character
        $sql = preg_replace("/(;\r)$/si", '', $sql);                // remove last ";\r"
        $sql_queries = explode(";\r", trim($sql));

        // connect to the database
        $dsn = self::$parameters['db']['driver']
            .':dbname='.self::$parameters['db']['database']
            .';host='.self::$parameters['db']['host']
            .';port='.self::$parameters['db']['port'];
        $dbtest = new \PDO($dsn, self::$parameters['db']['username'], self::$parameters['db']['password']);

        // execute all queries
        @$dbtest->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($sql_queries as $query) {
            $dbtest->query($query);
        }
        @$dbtest->query('SET FOREIGN_KEY_CHECKS=1');

        $dbtest = null; // close the database connection
    }

    /**
     * @Then /^wait "(\d+)" second[s]?$/
     *
     * Examples:
     *     Then wait "1" second
     *     Then wait "3" seconds
     */
    public function waitSeconds($delay)
    {
        sleep($delay);
    }

    /**
     * @Then /^echo last response$/
     *
     * Example:
     *     Then echo last response
     */
    public function echoLastResponse()
    {
        $this->printDebug($this->requestUrl."\n\n".$this->response);
    }

    /**
     * @Given /^that header property "([^"]*)" is "([^\n]*)"$/
     *
     * Example:
     *     Given that header property "Test" is "12345"
     */
    public function thatHeaderPropertyIs($propertyName, $propertyValue)
    {
        if (($propertyValue === 'null')) {
            return;
        }
        $this->reqHeaders[$propertyName] = $propertyValue;
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
        $body = $this->restObj;
        if (!is_string($body)) {
            $body = (array)$this->restObj;
        }
        if (in_array($method, array('get', 'head', 'delete'))) {
            $url = $this->requestUrl;
            if (is_array($body)) {
                $url .= '?'.http_build_query($body);
            }
            $this->response = $this->client->$method($url)->send();
        } elseif (in_array($method, array('post', 'put', 'patch'))) {
            $this->response = $this->client->$method($this->requestUrl, $headers, $body)->send();
        }
    }
}
