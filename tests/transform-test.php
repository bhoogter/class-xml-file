<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class transform_test extends TestCase
{
    const XML_FILE = __DIR__ . "/resources/test-xml-for-xsl.xml";
    const XSL_FILE = __DIR__ . "/resources/test-xsl.xml";

    public function testTransform(): void
    {
        $f = new xml_file(self::XML_FILE);
        $s = new xml_file(self::XSL_FILE);
        $this->assertEquals("DOMDocument", get_class($f->transformToDoc(self::XSL_FILE)));
        $this->assertEquals("DOMDocument", get_class($f->transformToDoc($s)));
        $this->assertFalse(strpos($f->saveXML(), "Collection") !== false);
        $this->assertTrue($f->transform($s));
        $this->assertTrue(strpos($f->saveXML(), "Collection") !== false);
    }

    public function testTransformXSL(): void
    {
        $f = new xml_file(self::XML_FILE);
        $s = file_get_contents(self::XSL_FILE);
        $this->assertTrue($f->transformXSL($s));
        $this->assertTrue(strpos($f->saveXML(), "Collection") !== false);
    }

    public function testTransformStatic(): void
    {
        $result = xml_file::transform_static(self::XML_FILE, self::XSL_FILE);
        $this->assertTrue(strpos($result->saveXML(), "Collection") !== false);
    }

    public function testTransformXSLStatic(): void
    {
        $result = xml_file::transformXSL_static(self::XML_FILE, file_get_contents(self::XSL_FILE));
        $this->assertTrue(strpos($result->saveXML(), "Collection") !== false);
    }

    public function testTransformXMLStatic(): void
    {
        $result = xml_file::transformXML_static(file_get_contents(self::XML_FILE), self::XSL_FILE);
        $this->assertTrue(strpos($result->saveXML(), "Collection") !== false);
    }

    public function testTransformXMLXSLStatic(): void
    {
        $x = file_get_contents(self::XML_FILE);
        $y = file_get_contents(self::XSL_FILE);
        $result = xml_file::transformXMLXSL_static($x, $y);
        $this->assertTrue(strpos($result->saveXML(), "Collection") !== false);
    }
}
