<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class xml_file_merge_test extends TestCase
{
    const SCAN_1 = __DIR__ . "/resources/test-xml-??.xml";

    public function testMergeList() {
        $obj = new xml_file();
        $result = $obj->merge_list(self::SCAN_1);
        $this->assertEquals(2, count($result), "Assure both files are scanned");
    }

    public function testLoadMerge(): void
    {
        $obj = new xml_file();
        $this->assertNotNull($obj);

        $obj->merge(self::SCAN_1, 'information', 'set');
        print "\n---------------\n" . $obj->saveXML();

        $result = $obj->get("//information/set[@id=2]/x");
        
        print "\nresult=$result";
        $this->assertEquals(2, $result, "Get merged value for id=2, x:");
            //    $this->assertEquals("Get merged value for id=2, x:", 2, $result);
    }
}
