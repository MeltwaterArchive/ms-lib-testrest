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

use \DataSift\TestRest\RestContext;
use \Behat\Behat\Context\BehatContext;

/**
 * FeatureContext
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/ms-lib-testrest
 */
class FeatureContext extends BehatContext
{
    /**
     * Initializes the BeHat feature context.
     * NOTE: Every scenario gets it's own context object.
     *
     * @param array $parameters Context parameters defined in behat.yml (default.context.parameters...)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('RestContext', new RestContext($parameters));
    }
}
