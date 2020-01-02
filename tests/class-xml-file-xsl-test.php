<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require(__DIR__ . "/class-xml-file-test.php");

final class xml_file_lint_test extends xml_file_test
{

    public function testReadXML(): void
    {
        $xml = $this->createTestXML();
        $xsl = $this->createTestXSL();
        $result = xml_file::transformXMLXSL_static($xml, $xsl);

        $this->assertEquals(0, 1);
    }
}
