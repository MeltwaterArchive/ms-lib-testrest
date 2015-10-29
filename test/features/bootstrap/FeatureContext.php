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

use \Behat\Behat\Context\BehatContext;

/**
 * FeatureContext
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file 
 * @link        https://github.com/datasift/ms-lib-testrest
 */
class FeatureContext extends BehatContext
{
    /**
     * Initializes the BeHat feature context.
     *
     * @param array $parameters Context parameters defined in behat.yml (default.context.parameters...)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('CustomContext', new CustomContext($parameters));
    }
}
