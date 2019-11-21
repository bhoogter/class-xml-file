<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require(__DIR__."/../src/class-xml-file.php");

class xml_file_test extends TestCase
{
    public $files;
    function tearDown(): void
    {
        foreach ($this->files as $tmp) @unlink($tmp);
    }

    function tmpFile()
    {
        if ($this->files == null) $files = array();
        $this->files[] = $tmp = tempnam('/tmp', 'test_');
        unlink($tmp);
        return $tmp;
    }

    public function testCreateXMLFile(): void
    {
        $subject = new xml_file();
        $this->assertNotNull($subject);
    }

    public function testTempFile(): void
    {
        $testText = "12345";
        $fname = $this->tmpFile();
        file_put_contents($fname, $testText);
        $result = file_get_contents($fname);
        $this->assertEquals($testText, $result);
    }

}
