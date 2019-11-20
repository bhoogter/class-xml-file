<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require('class-xml-file-test.php');
final class xml_file_read_test extends xml_file_test
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
    function createTestXML() {
        $tmp = $this->tmpFile();
        $s = "";
        $s += "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>";
        $s += "<items>" + "\n";
        $s += "  <items id='1'>" + "\n";
        $s += "    <name>Name #1</name>" + "\n";
        $s += "    <size>Small</size>" + "\n";
        $s += "  </item>" + "\n";
        $s += "<items>" + "\n";
        $s += "  <items id='2'>" + "\n";
        $s += "    <name>Name #2</name>" + "\n";
        $s += "    <size>Medium</size>" + "\n";
        $s += "  </item>" + "\n";
        $s += "<items>" + "\n";
        $s += "  <items id='3'>" + "\n";
        $s += "    <name>Name #3</name>" + "\n";
        $s += "    <size>Large</size>" + "\n";
        $s += "  </item>" + "\n";
        $s += "</items>";
        file_put_contents($tmp, $s);
        return $tmp;
    }
    
    public function testReadXML(): void
    {
        $tmp = $this->createTestXML();
        $subject = new xml_file($tmp);
        
        $result = $subject->get("/items/item[id=2]/name");
        
        $this->assertEquals("Name #2", $result);
    }
}
