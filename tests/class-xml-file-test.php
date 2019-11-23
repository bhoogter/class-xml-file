<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require(__DIR__ . "/../src/class-xml-file.php");

class xml_file_test extends TestCase
{
    public $files;

    public function tearDown(): void
    {
        if (is_array($this->files))
            foreach ($this->files as $tmp) @unlink($tmp);
    }

    public function tmpFile()
    {
        if ($this->files == null) $files = array();
        $this->files[] = $tmp = tempnam('/tmp', 'test_');
        unlink($tmp);
        return $tmp;
    }

    function createTestXML()
    {
        $tmp = $this->tmpFile();
        $s = "";
        $s .= "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>\n";
        $s .= "<items>" . "\n";
        $s .= "  <item id='1'>" . "\n";
        $s .= "    <name>Name #1</name>" . "\n";
        $s .= "    <size>Small</size>" . "\n";
        $s .= "  </item>" . "\n";
        $s .= "  <item id='2'>" . "\n";
        $s .= "    <name>Name #2</name>" . "\n";
        $s .= "    <size>Medium</size>" . "\n";
        $s .= "  </item>" . "\n";
        $s .= "  <item id='3'>" . "\n";
        $s .= "    <name>Name #3</name>" . "\n";
        $s .= "    <size>Large</size>" . "\n";
        $s .= "  </item>" . "\n";
        $s .= "</items>";
        file_put_contents($tmp, $s);
        return $tmp;
    }

    function createInvalidXML()
    {
        $tmp = $this->tmpFile();
        $s = "";
        $s .= "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>\n";
        $s .= "<items>" . "\n";
        $s .= "  <item id='1'>" . "\n";
        $s .= "</items>";
        file_put_contents($tmp, $s);
        return $tmp;
    }

    public function testCreateXMLFile(): void
    {
        $subject = new xml_file();
        $this->assertNotNull($subject);
    }

    public function testTempFile(): void
    {
        $testText = "12345";
        $fname = $this->tmpFile();
        file_put_contents($fname, $testText);
        $result = file_get_contents($fname);
        $this->assertEquals($testText, $result);
    }

    public function testLoadAFile(): void
    {
        $subject = new xml_file($fname = $this->createTestXML());
        $this->assertNotEquals("", $subject->gid);
        $this->assertEquals($fname, $subject->filename);
        $this->assertTrue($subject->loaded);
        $this->assertEquals("", $subject->err);
    }

    public function testClearObject(): void
    {
        $subject = new xml_file($fname = $this->createTestXML(), "readonly,notidy");
        $this->assertTrue($subject->loaded);
        $this->assertTrue($subject->readonly);
        $this->assertTrue($subject->notidy);
        $gid = $subject->gid;

        $subject->clear();
        $this->assertFalse($subject->loaded);
        $this->assertEquals("", $subject->filename);
        $this->assertEquals($gid, $subject->gid);
        $this->assertFalse($subject->readonly);
        $this->assertFalse($subject->notidy);
    }

    public function testLoadNonExistentFile(): void
    {
    $subject = new xml_file();
    $result = $subject->load($this->tmpFile());
    $this->assertFalse($subject->loaded);
    $this->assertFalse($result);
    }

    public function testInvalidXML(): void
    {
        $failed = false;
        $subject = null;
        try {
            $subject = new xml_file($this->createInvalidXML());
        } catch (Exception $e) {
            $failed = true;
        }

        $this->assertTrue($failed);
        $this->assertNotEquals("", $subject->err);
    }


}
