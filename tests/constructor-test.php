<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class converters_test extends TestCase
{
    const XML_TEXT = "<?xml version='1.0' ?>\n<a><b><c /></b><d /></a>";
    const XML_FILE = __DIR__ . "/resources/test-xml-01.xml";

    private static function getXmlFile() { return new xml_file(self::XML_FILE);    }
    private static function getDoc() { return self::getXmlFile()->Doc; }
    private static function getDocEl() { return self::getDoc()->firstChild->childNodes[1]; }

    public function testCreateWithoutParams(): void
    {
        $this->assertNotNull(new xml_file());
    }

    public function testCloneByObject(): void
    {
        $this->assertNotNull(new xml_file(self::getXmlFile()));
        $this->assertNotNull(new xml_file(self::getDoc()));
        $this->assertNotNull(new xml_file(self::getDocEl()));
    }
    
    public function testClonebyString(): void
    {
        $this->assertNotNull(new xml_file(self::XML_FILE));
        $this->assertNotNull(new xml_file(self::XML_TEXT));
    }
}
