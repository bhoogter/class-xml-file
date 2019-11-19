<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class xml_file_test extends TestCase
{
    public $files;
    function tearDown()
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
    function createTestXML() {
        $tmp = $this->tmpFile();
        $s = "";
        $s += "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>
        file
    }
    
    public function testReadXML(): void
    {
        $subject = new xml_file();
        $this->assertNotNull($subject);
    }
    public function testTempFile(): void
    {
        $testText = "12345";
        $fname = $this->tmpFile();
        file_put_contents($testText);
        $result = file_get_contents($fname);
        $this->assertEquals($testText, $result);
    }
}
