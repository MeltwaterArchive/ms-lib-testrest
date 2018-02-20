<?php

namespace DataSift\BehatExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Webmozart\Assert\Assert;

class FileContext extends File implements FileAwareContext
{
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
        Assert::fileExists($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }
}
