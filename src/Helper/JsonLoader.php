<?php

namespace DataSift\BehatExtension\Helper;

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
