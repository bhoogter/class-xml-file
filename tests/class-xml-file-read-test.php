<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require(__DIR__."/class-xml-file-test.php");

final class xml_file_read_test extends xml_file_test
{
    function createTestXML() {
        $tmp = $this->tmpFile();
        $s = "";
        $s .= "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>\n";
        $s .= "<items>" . "\n";
        $s .= "  <item id='1'>" . "\n";
        $s .= "    <name>Name #1</name>" . "\n";
        $s .= "    <size>Small</size>" . "\n";
        $s .= "  </item>" . "\n";
        $s .= "  <item id='2'>" . "\n";
        $s .= "    <name>Name #2</name>" . "\n";
        $s .= "    <size>Medium</size>" . "\n";
        $s .= "  </item>" . "\n";
        $s .= "  <item id='3'>" . "\n";
        $s .= "    <name>Name #3</name>" . "\n";
        $s .= "    <size>Large</size>" . "\n";
        $s .= "  </item>" . "\n";
        $s .= "</items>";
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
