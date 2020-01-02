<?php

require_once(__DIR__ . "/class-test-base.php");

final class xml_file_xsl_test extends test_base
{

    public function testReadXML(): void
    {
        $xml = file_get_contents($this->createTestXML());
        $xsl = file_get_contents($this->createTestXSL());
        $result = xml_file::transformXMLXSL_static($xml, $xsl)->saveXML();

        $this->assertTrue(strpos($result, "Name #1,Name #2,Name #3") !== false);
    }
}
