<?php
/**
 * This file is part of DataSift\TestRest project.
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file
 * @link        https://github.com/datasift/ms-lib-testrest
 */

namespace Test;

class BaseContextTest extends \PHPUnit_Framework_TestCase
{
    protected $obj = null;
    
    public function setUp()
    {
        //$this->markTestSkipped(); // skip this test

        $parameters = array(
            'alpha'    => 'beta',
            'gamma'    => 123,
            'delta'    => true,
            'base_url' => 'http://localhost:8081',
            'db' => array(
                'driver'     => 'mysql',
                'database'   => 'testrest_test',
                'host'       => '127.0.0.1',
                'port'       => 3306,
                'username'   => 'testrest',
                'password'   => 'testrest',
                'sql_schema' => '/../../../test/resources/database/schema.sql',
                'sql_data'   => '/../../../test/resources/database/data.sql'
            ),
            'memcached' => array(
                'host' => '127.0.0.1',
                'port' => 11211
            )
        );
        $this->obj = new \DataSift\TestRest\BaseContext($parameters);
    }

    public function testGetParameter()
    {
        $this->assertEquals('beta', $this->obj->getParameter('alpha'));
        $this->assertEquals(123, $this->obj->getParameter('gamma'));
        $this->assertTrue($this->obj->getParameter('delta'));
        $this->assertCount(8, $this->obj->getParameter('db'));
    }

    public function testGetParameterMissing()
    {
        $obj = new \DataSift\TestRest\BaseContext(array());
        $this->setExpectedException(
            'Exception',
            'Context Parameters not loaded!'
        );
        $obj->getParameter('missing');
    }

    public function testSetupDatabase()
    {
        $obj = $this->obj;
        $obj::setupDatabase();

        $obj = new \DataSift\TestRest\BaseContext(array());
        $obj::setupDatabase();
    }

    public function testFlushCache()
    {
        $obj = $this->obj;
        $obj::flushCache();

        $obj = new \DataSift\TestRest\BaseContext(array());
        $obj::flushCache();
    }
}
