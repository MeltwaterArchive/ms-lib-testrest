<?php
/**
 * This file is part of DataSift\TestRest project.
 *
 * @category    Library
 * @package     DataSift\Behat
 * @author      Michael Heap <michael@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file
 * @link        https://github.com/datasift/ms-lib-behat
 */

namespace DataSift\TestRestExtension\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use DataSift\TestRestExtension\Helper\JsonLoader;
use GuzzleHttp\ClientInterface;

/**
 * DataSift\Behat\MountebankContext
 *
 * @category    Library
 * @package     DataSift\Behat
 * @author      Michael Heap <michael@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file
 * @link        https://github.com/datasift/ms-lib-behat
 */
class MountebankContext implements ApiClientAwareContext, MountebankAwareContext
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $mountebank = array();

    /**
     * @var array
     */
    protected $mocks = array();

    /**
     * @var array
     */
    protected $defaults = array();

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Sets the mountebank config
     *
     * @param array $config
     *
     * @return void
     */
    public function setMountebankConfig(array $config)
    {
        $this->mountebank = $config;
    }

    /**
     * Simple getter for getting a mountebank setting
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array
     */
    public function getMountebankSetting($key, $default = false)
    {
        return isset($this->mountebank[$key]) ? $this->mountebank[$key] : $default;
    }

//    /**
//     * @BeforeSuite
//     */
//    public static function setup(BeforeSuiteScope $event)
//    {
//        if (isset($params['mountebank']['command'], $params['mountebank']['on_setup']) && $params['mountebank']['on_setup']) {
//            static::startUpMountebank($params['mountebank']['command']);
//        }
//    }

    /**
     * Setup Scenario
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $event
     *
     * @throws \Exception
     */
    public function setupScenario(BeforeScenarioScope $event)
    {
        $scenario = $event->getScenario();
        $feature = $event->getFeature();
        foreach (array_merge($scenario->getTags(), $feature->getTags()) as $tag) {
            // Default configured mock
            if ('mountebank' === $tag && $this->getMountebankSetting('default_mock_status')) {
                $this->theDefaultMockShouldReturn($this->getMountebankSetting('default_mock_status'));
            }

            if ('mountebank' === $tag && ! static::isMountebankRunning()) {
                static::startUpMountebank($this->getMountebankSetting('command'));
                break;
            }
        }
    }

    /** @AfterScenario */
    public function teardownScenario(AfterScenarioScope $event)
    {
        if ($this->getMountebankSetting('persist', true)) {
            $this->clearMountebank();
        } else {
            static::haltMountebank();
        }
    }

    /**
     * Setup mountebank
     *
     * @param $cmd
     *
     * @throws \Exception
     */
    protected static function startUpMountebank($cmd)
    {
        $dangerous = array(
            "sudo ", "rm "
        );

        foreach ($dangerous as $v) {
            if (strpos($cmd, $v) !== false) {
                throw new \Exception("Potentially dangerous command provided as mountebank.command: " . $cmd);
            }
        }

        exec("{$cmd} --allowInjection > /dev/null & echo $! > /tmp/_behat_mountebank_pid");

        // It takes about a second to start. This sucks, I know
        sleep(2); // Adding a little longer to prevent any issues
    }

    /**
     * Stop the mountebank process
     *
     * @AfterSuite
     */
    public static function haltMountebank()
    {
        if (static::isMountebankRunning()) {
            $file = '/tmp/_behat_mountebank_pid';
            $pid = file_get_contents($file);

            posix_kill((int) $pid, SIGKILL);
            unlink($file);
        }
    }

    /**
     * Delete all mocks from mountebank
     *
     * @Given /^all mocks are removed$/
     */
    public function clearMountebank()
    {
        if (static::isMountebankRunning()) {
            $this->mocks = array();
            $mbUrl = $this->mountebank['url'].'/imposters';
            $request = $this->getClient()->createRequest(
                'delete',
                $mbUrl
            );

            $this->getClient()->send($request);
        }
    }

    protected static function isMountebankRunning()
    {
        return (bool) trim(`ps aux | grep "node .*mb --allowInjection$"`) != '';
    }

    /**
     * @Given /^Mountebank is running$/
     */
    public function mountebankIsRunning()
    {
        if (! static::isMountebankRunning()) {
            throw new \Exception("Mountebank is not running");
        }
    }

    /**
     * @Given /^Mountebank is not running$/
     */
    public function mountebankIsNotRunning()
    {
        if (static::isMountebankRunning()) {
            throw new \Exception("Mountebank is running");
        }
    }

    /**
     * @Given /^a mock exists at "([^"]*)" it should return "([^"]*)" with the body:$/
     */
    public function aMockExistsAtItShouldReturnWithTheBody($url, $httpCode, PyStringNode $body)
    {
        return $this->aMockExistsAtItShouldReturnWithTheContentTypeAndTheBody(
            $url,
            $httpCode,
            "application/json",
            $body
        );
    }

    /**
     * @Given /^a mock exists at "([^"]*)" it should return "([^"]*)"$/
     */
    public function aMockExistsAtItShouldReturn($url, $httpCode)
    {
        return $this->aMockExistsAtItShouldReturnWithTheContentTypeAndTheBody(
            $url,
            $httpCode,
            "application/json"
        );
    }

    /**
     * @Given /^the default mock should return "([^"]*)"$/
     */
    public function theDefaultMockShouldReturn($httpCode)
    {
        $parts = $this->parseUrl('/');
        $port = $parts['port'];

        $this->defaults[$port] = array(
            "type" => "is",
            "statusCode" => $httpCode,
            "headers" => array(
                "Content-Type" => "application/json",
                "Transfer-Encoding" => ""
            )
        );
    }

    /**
     * @Given /^a mock exists at "([^"]*)" it should return "([^"]*)" with the Content-Type "([^"]*)" and the body:$/
     */
    public function aMockExistsAtItShouldReturnWithTheContentTypeAndTheBody(
        $url,
        $httpCode,
        $contentType,
        PyStringNode $body = null
    ) {

        $parts = $this->parseUrl($url);
        $port = $parts['port'];

        if (!isset($this->mocks[$port])) {
            $this->mocks[$port] = array();
        }

        $this->mocks[$port][] = array(
            "url" => $url,
            "type" => "is",
            "statusCode" => $httpCode,
            "headers" => array(
                "Content-Type" => $contentType,
                "Transfer-Encoding" => ""
            ),
            "body" => trim((string) $body)
        );
    }

    /**
     * @Given /^a mock exists at "([^"]*)" it should return "([^"]*)" with a generated response where:$/
     */
    public function aMockExistsAtItShouldReturnWithAGeneratedResponseWhere($url, $httpCode, PyStringNode $response)
    {
        $parts = $this->parseUrl($url);
        $port = $parts['port'];

        if (!isset($this->mocks[$port])) {
            $this->mocks[$port] = array();
        }

        $headers = array(
            "Location" => $url,
            "Content-Type" => "application/json"
        );

        $jsResponse = array();
        foreach ($response as $line) {
            $parts = explode(" = ", $line);

            $depth = explode(".", $parts[0]);
            $currentLevel = &$jsResponse;
            foreach ($depth as $v) {
                if (!isset($currentLevel[$v])) {
                    $currentLevel[$v] = array();
                    $currentLevel = &$currentLevel[$v];
                }
            }
            $currentLevel = $parts[1];
        }

        $generatedResponse = json_encode($jsResponse);

        // Make the dynamic input stuff work
        $generatedResponse = preg_replace("/\"(input.\w+)\"/", '\1', $generatedResponse);

        $this->mocks[$port][] = array(
            "url" => $url,
            "type" => "inject",
            "body" => 'function (request, state, logger) {
                var input = JSON.parse(request.body);
                var resp = '.$generatedResponse.';

                return {
                    headers: '.json_encode($headers).',
                    body: JSON.stringify(resp)
                }
}'
        );
    }

    /**
     * @Given /^a mock exists at "([^"]*)" it should return "([^"]*)" with the fixture "([^"]*)"$/
     */
    public function aMockExistsAtItShouldReturnWithTheFixture($url, $httpCode, $fixturePath)
    {
        try {
            $fixturePath = strtolower(str_replace(" ", "-", $fixturePath));
            $info = JsonLoader::getData($fixturePath);
            $body = new PyStringNode(array(json_encode($info)), 0);

            return $this->aMockExistsAtItShouldReturnWithTheContentTypeAndTheBody(
                $url,
                $httpCode,
                "application/json",
                $body
            );
        } catch (\Exception $ex) {
            throw new \Exception("Fixture file is not found" . $ex->getMessage());
        }
    }

    /**
     * @Given /^a mock exists at "([^"]*)" that represents the HTTP response:$/
     */
    public function aMockExistsAtThatRepresentsTheHttpResponse($url, PyStringNode $response)
    {
        // Parse HTTP string
        $lines = explode("\n", (string) $response->getRaw());
        preg_match("#HTTP/1\.\d (\d+)#", array_shift($lines), $matches);
        $httpCode = $matches[1];

        // Headers
        $body = array();
        $headers = array();
        foreach ($lines as $index => $line) {
            if (strpos($line, ":")) {
                list($k,$v) = explode(":", $line, 2);
                $headers[$k] = $v;
                unset($lines[$index]);
            } else {
                $body[] = $line;
            }
        }

        // Body
        $body = implode("\n", $body);

        // Register with Mountebank
        $parts = $this->parseUrl($url);
        $port = $parts['port'];

        if (!isset($this->mocks[$port])) {
            $this->mocks[$port] = array();
        }

        $this->mocks[$port][] = array(
            "url" => $url,
            "type" => "is",
            "statusCode" => $httpCode,
            "headers" => $headers,
            "body" => trim($body)
        );
    }

    /**
     * @Given /^the mocks are created$/
     */
    public function theMocksAreCreated()
    {
        $stubs = array();

        // Adding defaults (the order is important must be last !!!)
        foreach ($this->defaults as $port => $default) {
            $this->mocks[$port][] = $default;
        }

        foreach ($this->mocks as $port => $mocks) {
            foreach ($mocks as $m) {
                $url = isset($m['url']) ? $m['url'] : false;
                $type = $m['type'];
                unset($m['type'], $m['url']);

                // If it's an inject type, we don't need
                // an envelope to send in
                if ($type == 'inject') {
                    $m = $m['body'];
                }

                $mock = array(
                    "responses" => array(
                        array(
                            $type => $m
                        )
                    )
                );

                // Do we have a URL to enforce
                if ($url) {
                    $mock['predicates'] = array(
                        array(
                            "equals" => array(
                                "path" => $url
                            )
                        )
                    );
                }

                $stubs[] = $mock;
            }

            $mbUrl = $this->mountebank['url'].'/imposters';

            $body = array(
                'port' => $port,
                'protocol' => 'http',
                'stubs' => $stubs
            );

            $request = $this->getClient()->createRequest(
                'post',
                $mbUrl,
                array(
                    'body' => json_encode($body)
                )
            );

            $this->getClient()->send($request);
        }
    }

    protected function parseUrl($url) {
        // Get everything in the format we need
        $parts = parse_url($url);
        $default = parse_url($this->mountebank['default_mock_host']);

        foreach (array("scheme", "host", "port") as $k) {
            if (!isset($parts[$k])) {
                $parts[$k] = $default[$k];
            }
        }

        return $parts;
    }

    protected function getClient()
    {
        if (null === $this->client) {
            throw new \RuntimeException('Client has not been set');
        }

        return $this->client;
    }
}
