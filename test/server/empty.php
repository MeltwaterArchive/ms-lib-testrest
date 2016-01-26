<?php
/**
 * Test Server (another example)
 *
 * @category    Library
 * @package     DataSift\TestRest
 * @author      Nathan Macnamara <nathan.macnamara@datasift.com>
 * @copyright   2016-2016 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file
 * @link        https://github.com/datasift/ms-lib-testrest
 */

header("HTTP/1.0 204 No Content");
header('Content-Type: application/json');
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
header('Pragma: public');
header('Expires: Thu, 04 jan 1973 00:00:00 GMT'); // Date in the past
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
