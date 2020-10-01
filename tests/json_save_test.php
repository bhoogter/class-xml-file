<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class json_save_test extends TestCase
{
    public function testjsonToDomDocument_xslt_isValidFormed(): void
    {
        $result = xml_file::saveJsonXsltStandard();
        $subject = new xml_file($result);
        $this->assertTrue($subject->loaded);
    }

    public function testjsonToDomDocument_xsltRecordset_isValidFormed(): void
    {
        $result = xml_file::saveJsonXsltRecordset();
        $subject = new xml_file($result);
        $this->assertTrue($subject->loaded);
    }

    public function testTidyStatic(): void
    {
        $source = file_get_contents(__DIR__ . '/resources/test-json-tidy.json');
        $result = xml_file::tidyJson_string($source);
        $this->assertTrue(substr_count($result, "\n") > 0);
    }

    public function testTidyStaticMinify(): void
    {
        $source = file_get_contents(__DIR__ . '/resources/test-json-tidy.json');
        $result = xml_file::tidyJson_string($source, 0);
        $this->assertEquals(0, substr_count($result, "\n"));
    }

    public function testjsonToDomDocument_loadAndSaveJson(): void
    {
        $subject = new xml_file();
        $subject->loadJson(__DIR__ . "/resources/test-json-simple.json", "jsonData");
        $this->assertTrue($subject->loaded);

// print "\nXML: ". $subject->saveXML();
        $result = $subject->saveJson();
// print "\nJSON: ". $result;
        $this->assertTrue(strpos($result, '"c": "4.5",') !== false);
        $this->assertTrue(strpos($result, '"cc": "44.55",') !== false);
    }

    public function testSaveXMLRecordsetToJson()
    {
        $subject = new xml_file(__DIR__ . "/resources/recordset.xml");
        $result = $subject->saveJson();
        // print xml_file::tidyJson_string($result);

        $this->assertNotNull($result);
        $this->assertTrue(false !== strpos($result, '"id": "option1"'));
    }

    public function testSaveXMLRecordsetToJsonRecordset()
    {
        $subject = new xml_file(__DIR__ . "/resources/recordset.xml");
        $result = $subject->saveJson('recordset');

        print($result);
        $this->assertNotNull($result);
    }
}
