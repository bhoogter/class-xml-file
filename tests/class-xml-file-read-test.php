<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/autoload-test.php");

final class xml_file_read_test extends xml_file_test_utils
{

    public function testReadXML(): void
    {
        $tmp = $this->createTestXML();
        $subject = new xml_file($tmp);

        $result = $subject->get("/items/item[@id=2]/name");

        $this->assertEquals("Name #2", $result);
    }
}
