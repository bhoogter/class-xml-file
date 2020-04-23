<?php

require_once(__DIR__ . "/class-test-base.php");

final class xml_read_test extends test_base
{
    public function testReadXML(): void
    {
        $tmp = $this->createTestXML();
        $subject = new xml_file($tmp);

        $result = $subject->get("/items/item[@id=2]/name");

        $this->assertEquals("Name #2", $result);
    }

    public function testXMLFileCount(): void
    {
        $f = __DIR__ . '/resources/test-data-list.xml';
        $subject = new xml_file($f);
        $this->assertEquals(5, $subject->cnt("/*/item"));
        $this->assertEquals(5, $subject->count_parts("/list/item"));
    }

    public function testXMLFileList(): void
    {
        $subject = new xml_file(__DIR__ . '\resources\test-data-list.xml');
        $result = $subject->lst("//item");
        $this->assertEquals(5, sizeof($result));
        $this->assertTrue(strpos($result[2], "Name #3") !== false);
    }

    public function testXMLFileDef(): void
    {
        $subject = new xml_file(__DIR__ . '\resources\test-data.xml');
        $result = $subject->def("//item[@id=2]/name");
        $this->assertEquals("<name>Name #2</name>", $result);
    }

    public function testXMLFileNde(): void
    {
        $subject = new xml_file(__DIR__ . '\resources\test-data.xml');
        $result = $subject->nde("//item[@id=2]");
        $this->assertTrue(is_object($result));
        $this->assertEquals("DOMElement", get_class($result), "Node is returned");
    }

    public function testXMLFileSaveXML(): void
    {
        $subject = new xml_file(__DIR__ . '\resources\test-data.xml');
        $result = $subject->saveXML();
        $this->assertTrue(strpos($result, "<name>Name #2</name>") !== false);
        $this->assertTrue(strpos($result, "<name>Name #1</name>") !== false);
    }
}
