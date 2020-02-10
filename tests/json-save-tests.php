<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class json_save_tests extends TestCase
{
    public function testjsonToDomDocument_xsltIsValidFormed(): void
    {
        $result = xml_file::saveJsonXslt();
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

        $result = $subject->saveJson();
        $this->assertTrue(strpos($result, '"c": "4.5",') !== false);
        $this->assertTrue(strpos($result, '"cc": "44.55",') !== false);
    }
}
