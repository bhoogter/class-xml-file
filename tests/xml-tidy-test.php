<?php

require_once(__DIR__ . "/class-test-base.php");

final class xml_tidy_test extends test_base
{

    public function testWriteNewItemToXML(): void
    {
        $subject = xml_file::make_tidy_string("<?xml version='1.0' ?>\n<a><b><c /></b><d><e></e><f></f></d></a>");
        $count = substr_count($subject, "\n");

        if (class_exists("tidy"))
            $this->assertEquals(9, $count);
        else
            $this->assertEquals(1, $count);
    }
}
