<?php

namespace DataSift\TestRestExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Process\Process;
use PHPUnit_Framework_Assert as Assertions;

class FileContext implements FileAwareContext
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
        $this->clearDirectory($this->workingDir);

        @mkdir($this->workingDir, 0777, true);
    }

    /**
     * Creates a file with specified name and context in current working dir.
     *
     * @Given /^(?:there is )?a file named "([^"]*)" with:$/
     *
     * @param string       $filename name of the file (relative path)
     * @param PyStringNode $content  PyString string instance
     */
    public function aFileNamedWith($filename, PyStringNode $content)
    {
        $content = strtr((string) $content, array("'''" => '"""'));
        $this->createFile($this->workingDir . '/' . $filename, $content);
    }

    /**
     * Checks whether a file at provided path exists.
     *
     * @Given /^file "([^"]*)" should exist$/
     *
     * @param   string $path
     */
    public function fileShouldExist($path)
    {
        Assertions::assertFileExists($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }

    private function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    private function clearDirectory($path)
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
