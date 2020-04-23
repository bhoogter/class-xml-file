<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class converters_test extends TestCase
{
    const XML_TEXT = "<?xml version='1.0' ?>\n<a><b><c /></b><d /></a>";
    const XML_FILE = __DIR__ . "/resources/test-xml-01.xml";

    private function getDoc()
    {
        return (new xml_file(self::XML_FILE))->Doc;
    }

    private function getDocEl()
    {
        return $this->getDoc()->firstChild->childNodes[1];
    }

    public function testCheckExpectations(): void
    {
        $this->assertEquals("DOMDocument", get_class($this->getDoc()));
        $this->assertEquals("DOMElement", get_class($this->getDocEl()));
        $this->assertTrue(is_a($this->getDocEl(), "DOMNode"));
    }

    public function testBasicConverters(): void
    {
        $this->assertEquals("DOMDocument", get_class(xml_file::XMLToDoc(self::XML_TEXT)));
        $this->assertEquals("DOMDocument", get_class(xml_file::FileToDoc(self::XML_FILE)));
        $this->assertEquals("string", gettype(xml_file::DocToXML($this->getDoc())));
        $this->assertEquals("DOMDocument", get_class(xml_file::DocElToDoc($this->getDocEl())));
        
        $this->assertEquals("string", gettype(xml_file::nodeXml($this->getDocEl())));
        $this->assertEquals("xml_file", get_class(xml_file::nodeXmlFile($this->getDocEl())));
        $this->assertEquals("DOMDocument", get_class(xml_file::nodeXmlDoc($this->getDocEl())));
    }
}
