<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require(__DIR__ . "/class-xml-file-test.php");

final class xml_file_lint_test extends xml_file_test
{

    public function testWriteNewItemToXML(): void
    {
        $subject = xml_file::make_tidy_string("<?xml version='1.0' ?>\n<a><b><c /></b><d><e></e><f></f></d></a>");
        $count = substr_count($subject, "\n");

        if (class_exists("tidy"))
            $this->assertEquals(5, $count);
        else
            $this->assertEquals(1, $count);
    }
}
