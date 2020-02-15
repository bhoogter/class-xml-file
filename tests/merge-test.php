<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class xml_file_merge_test extends TestCase
{
    const SCAN_1 = __DIR__ . "/resources/test-xml-??.xml";

    const XML_1 = __DIR__ . "/resources/test-xml-01.xml";
    const XML_2 = __DIR__ . "/resources/test-xml-02.xml";
    const XML_3 = __DIR__ . "/resources/test-xml-03.xml";
    const XML_4 = __DIR__ . "/resources/test-xml-04.xml";

    const tagRoot = 'information';
    const tagItem = 'set';

    public function testMergeList() {
        $obj = new xml_file();
        $result = $obj->merge_list(self::SCAN_1);
        $this->assertEquals(4, count($result), "Assure both files are scanned");
    }

    public function testLoadMerge(): void
    {
        $obj = new xml_file();
        $this->assertNotNull($obj);

        $obj->merge(self::SCAN_1, self::tagRoot, self::tagItem);
// print "\n---------------\n" . $obj->saveXML();

        $result = $obj->get("//information/set[@id=2]/x");
        
// print "\nresult=$result";
        $this->assertEquals("4", $result, "Get merged value for id=2, x:");
            //    $this->assertEquals("Get merged value for id=2, x:", 2, $result);
    }

    public function testLoadMergeIndividual(): void
    {
        $obj = new xml_file();
        $this->assertNotNull($obj);

        $obj->merge(self::XML_1, self::tagRoot, self::tagItem);
        $obj->merge(self::XML_2, self::tagRoot, self::tagItem);

        $this->assertEquals(4, $obj->cnt("//information/set"), "Files are merged");
    }

    public function testLoadMergeToBase(): void
    {
        $obj = new xml_file(__DIR__ . "/resources/test-xml-base.xml");
        $this->assertNotNull($obj);

// print $obj->saveXML("tidy");
        $obj->merge_to(self::XML_1, self::tagRoot, self::tagItem, "//information/target/sub-level[@sel=3]");
        $obj->merge_to(self::XML_2, self::tagRoot, self::tagItem, "//information/target/sub-level[@sel=3]");

        $this->assertEquals(4, $obj->cnt("//information/target/sub-level[@sel=3]/set"), "Files are merged to target");
        $this->assertEquals("12", $obj->get("//information/target/sub-level[@sel=3]/set[@id=4]/z"), "Files are merged to target");
    }
}
