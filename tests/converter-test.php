<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class converter_test extends TestCase
{
    const XML_TEXT = "<?xml version='1.0' ?>\n<a><b><c /></b><d /></a>";
    const XML_FILE = __DIR__ . "/resources/test-xml-01.xml";

    private static function getXmlFile() { return new xml_file(self::XML_FILE); }
    private static function getDoc() { return self::getXmlFile()->Doc; }
    private static function getDocEl() { return self::getDoc()->firstChild->childNodes[1]; }

    public function testCheckExpectations(): void
    {
        $this->assertEquals("DOMDocument", get_class(self::getDoc()));
        $this->assertEquals("DOMElement", get_class(self::getDocEl()));
        $this->assertTrue(is_a(self::getDocEl(), "DOMNode"));
    }

    public function testBasicConverters(): void
    {
        $this->assertEquals("DOMDocument", get_class(xml_file::XMLToDoc(self::XML_TEXT)));
        $this->assertEquals("DOMDocument", get_class(xml_file::FileToDoc(self::XML_FILE)));
        $this->assertEquals("string", gettype(xml_file::DocToXML(self::getDoc())));
        $this->assertEquals("DOMDocument", get_class(xml_file::DocElToDoc(self::getDocEl())));

        $this->assertEquals("DOMDocument", get_class(xml_file::xmlDoc(self::XML_TEXT)));
        $this->assertEquals("string", gettype(xml_file::docXml(self::getDoc())));
        
        $this->assertEquals("string", gettype(xml_file::nodeXml(self::getDocEl())));
        $this->assertEquals("xml_file", get_class(xml_file::nodeXmlFile(self::getDocEl())));
        $this->assertEquals("DOMDocument", get_class(xml_file::nodeXmlDoc(self::getDocEl())));
    }

    public function testToXML(): void
    {
        $this->assertEquals("string", gettype(xml_file::toXML(self::XML_TEXT)));
        $this->assertEquals("string", gettype(xml_file::toXML(self::getXmlFile())));
        $this->assertEquals("string", gettype(xml_file::toXML(self::getDoc())));
        $this->assertEquals("string", gettype(xml_file::toXML(self::getDocEl())));
    }

    public function testToDoc(): void
    {
        $this->assertEquals("DOMDocument", get_class(xml_file::toDoc(self::XML_TEXT)));
        $this->assertEquals("DOMDocument", get_class(xml_file::toDoc(self::XML_FILE)));
        $this->assertEquals("DOMDocument", get_class(xml_file::toDoc(self::getXmlFile())));
        $this->assertEquals("DOMDocument", get_class(xml_file::toDoc(self::getDoc())));
        $this->assertEquals("DOMDocument", get_class(xml_file::toDoc(self::getDocEl())));
    }
}
