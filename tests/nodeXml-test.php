<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class node_xml_test extends TestCase
{
    public function testNodeXml(): void
    {
        $obj = new xml_file(__DIR__ . "/resources/test-data.xml");
        $element = $obj->nde("/items/item[@id='2']");
        $result = xml_file::nodeXml($element);
        $this->assertTrue(strpos($result, "<size>Medium</size>") != false);
    }

    public function testNodeXmlFile(): void
    {
        $obj = new xml_file(__DIR__ . "/resources/test-data.xml");
        $element = $obj->nde("/items/item[@id='2']");
        $result = xml_file::nodeXmlFile($element);
        $this->assertEquals("Medium", $result->get("/item/size"));
    }
}
