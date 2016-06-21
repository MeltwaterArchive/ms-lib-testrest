<?php

namespace DataSift\BehatExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Process\Process;
use PHPUnit_Framework_Assert as Assertions;

abstract class File implements FileAwareContext
{
    /**
     * @var string
     */
    protected $workingDir;

    /**
     * Sets the working path
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->workingDir = $path;
    }

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareScenario()
    {
        if (is_dir($this->workingDir)) {
            $this->clearDirectory($this->workingDir);
        }

        @mkdir($this->workingDir, 0777, true);
    }

    protected function openJSONFile($filename)
    {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), 1);
        }

        return array();
    }

    protected function saveJSONFile($filename, array $content)
    {
        $this->createFile($filename, json_encode($content));
    }

    protected function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    protected function clearDirectory($path)
    {
        $files = scandir($path);
        array_shift($files);
        array_shift($files);

        foreach ($files as $file) {
            $file = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                self::clearDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($path);
    }
}
