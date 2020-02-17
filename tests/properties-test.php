<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class properties_test extends TestCase
{
    const SCAN_1 = __DIR__ . "/resources/test-xml-??.xml";

    const XML_1 = __DIR__ . "/resources/test-xml-01.xml";
    const XML_2 = __DIR__ . "/resources/test-xml-02.xml";
    const XML_3 = __DIR__ . "/resources/test-xml-03.xml";
    const XML_4 = __DIR__ . "/resources/test-xml-04.xml";

    const tagRoot = 'information';
    const tagItem = 'set';

    public function testSetGetListHas() {
        $propName = "1";
        $propValue = "2";

        $obj = new xml_file();
        $this->assertNotNull($obj);

        $res = $obj->set_property($propName, $propValue);
        $this->assertEquals($propValue, $res, "Set returns value");
        $this->assertTrue($obj->has_property($propName), "Has Property returns true");
        $this->assertFalse($obj->has_property("fake property"), "But not for non existent");
        $this->assertContains($propName, $obj->get_property_list(), "Prop Name is in list of properties");

        $res = $obj->get_property($propName);
        $this->assertEquals($propValue, $res, "Get property returns expected result");
    }
}
