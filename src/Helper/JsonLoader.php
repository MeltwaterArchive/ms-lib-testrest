<?php
/**
 * This file is part of DataSift\Behat project.
 *
 * @category    Library
 * @package     DataSift\Helper
 * @author      Michael Heap <michael.heap@datasift.com>
 * @copyright   2015-2015 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file
 * @link        https://github.com/datasift/ms-lib-behat
 */

namespace DataSift\TestRestExtension\Helper;

/**
 * DataSift\TestRest\Exception
 *
 * Base Exception
 *
 * @category    Library
 * @package     DataSift\Helper
 * @author      Michael Heap <michael.heap@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd. <http://datasift.com>
 * @license     https://opensource.org/licenses/MIT The MIT License (MIT) - see the LICENSE file
 * @link        https://github.com/datasift/ms-lib-behat
 */
class JsonLoader
{
    static $filePath = '.';

    public static function setFilePath($filePath) {
        static::$filePath = $filePath;
    }

    public static function getFilePath() {
        return static::$filePath;
    }

    public static function loadJsonFile($fileName, $appendDotJson = true) {
        $ext = '';
        if ($appendDotJson) {
            $ext = '.json';
        }

        $path = static::$filePath.'/'.$fileName.$ext;

        $content = json_decode(file_get_contents($path), true);

        if (json_last_error()) {
            throw new \Exception("Unable to parse JSON in ".$path);
        }

        return $content;
    }

    public static function getData($fileName, $path = null) {
        $data = static::loadJsonFile($fileName);

        if (!$path) {
            return $data;
        }

        foreach (explode(".", $path) as $part) {
            if (!isset($data[$part])) {
                throw new \Exception("Invalid location '".$path."' in '".$fileName."'");
            }
            $data = $data[$part];
        }

        return $data;
    }
}
