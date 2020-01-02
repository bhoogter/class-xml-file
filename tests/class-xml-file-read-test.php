<?php

require_once(__DIR__ . "/class-test-base.php");

final class xml_file_read_test extends test_base
{

    public function testReadXML(): void
    {
        $tmp = $this->createTestXML();
        $subject = new xml_file($tmp);

        $result = $subject->get("/items/item[@id=2]/name");

        $this->assertEquals("Name #2", $result);
    }
}
