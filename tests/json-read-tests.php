<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class json_read_tests extends TestCase
{
    public function testjsonToDomDocument_jsonFileLoads(): void
    {
        $subject = new xml_file();
        $subject->loadJson(__DIR__ . "/resources/test-json-simple.json", "jsonData");
        $this->assertTrue($subject->loaded);

        $result = xml_file::make_tidy_string($subject->saveXML());

        $this->assertTrue(strpos($result, "<item>2</item>") !== false);
        $this->assertTrue(strpos($result, "<b>3</b>") !== false);
        $this->assertTrue(strpos($result, "<cc>44.55</cc>") !== false);

        $result = $subject->get("/jsonData/c");
        $this->assertEquals("3", $subject->get("/jsonData/b"));
    }

    public function testjsonToDomDocument_jsonStringLoads(): void
    {
        $subject = new xml_file();
        $contents = __DIR__ . "/resources/test-json-simple.json";
        $subject->loadJson($contents, "jsonData");
        $this->assertTrue($subject->loaded);
    }
}
