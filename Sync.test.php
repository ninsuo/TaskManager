<?php

require("Sync.php");

//die("Please read warning inside that file and comment this die() before running tests.");

/**
 * Test Case for Sync class
 *
 * Warning:
 * This test case creates a directory in /tmp, check that
 * permissions are granted and the 'sync' directory not
 * already in use.
 *
 * Requires PHPUnit (developped using 3.6.12)
 */
class SyncTest extends PHPUnit_Framework_TestCase
{

    const DIR = "/tmp/sync";

    public function setUp()
    {
        if (!is_dir(self::DIR))
        {
            if (!mkdir(self::DIR))
            {
                throw new \Exception(sprintf("Could not create test directory: %s\n", self::DIR));
            }
        }
    }

    public function tearDown()
    {
        // Removes all files and directories inside self::DIR
        $files = new \RecursiveIteratorIterator(
           new \RecursiveDirectoryIterator(self::DIR, \RecursiveDirectoryIterator::SKIP_DOTS),
           \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo)
        {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir(self::DIR);
    }

    public function testGetNoFile()
    {
        $file = self::DIR . "/test.sync";
        $sync = new Sync($file);

        $this->assertNull($sync->test);
        $this->assertFalse(is_file($file));
    }

    public function testGetFileExistsButNotReadable()
    {
        $file = self::DIR . "/test.sync";

        $fd = fopen($file, 'w');
        fclose($fd);
        chmod($file, 0000);

        $sync = new Sync($file);
        try
        {
            $test = $sync->test;
        }
        catch (\Exception $e)
        {
            $expected = sprintf("File '%s' is not readable.", $file);
            $this->assertEquals($expected, $e->getMessage());
            return;
        }

        $this->fail("Expected exception, but never raised.");
    }

    public function testGetPropertyNotFound()
    {
        $file = self::DIR . "/test.sync";
        $sync = new Sync($file);

        $this->assertNull($sync->not_found);
        $this->assertNull($sync->{0});
        $this->assertNull($sync->{'重庆'});
    }

    public function testGetSetSmallValue()
    {
        $file = self::DIR . "/test.sync";
        $sync = new Sync($file);

        $sync->data = "test a";
        $this->assertEquals("test a", $sync->data);

        $sync->{0} = "test b";
        $this->assertEquals("test b", $sync->{0});

        $sync->{'重庆'} = "test e";
        $this->assertEquals("test e", $sync->{'重庆'});
    }

    public function testGetSetBigValue()
    {
        $value = str_repeat("abcdefghijklmnopqrstuvwxyz", 64 * 1024 + 1);

        $file = self::DIR . "/test.sync";
        $sync = new Sync($file);

        $sync->data = $value;
        $this->assertEquals($value, $sync->data);

        $sync->{0} = $value;
        $this->assertEquals($value, $sync->{0});

        $sync->{'重庆'} = $value;
        $this->assertEquals($value, $sync->{'重庆'});
    }

    public function testSetDirectoryDoesNotExists()
    {
        $dir = self::DIR . "/xxx";
        $file = $dir . "/test.sync";

        $sync = new Sync($file);
        try
        {
            $sync->test = "fail";
        }
        catch (\Exception $e)
        {
            $expected = sprintf("Directory '%s' does not exist or is not writable.", $dir);
            $this->assertEquals($expected, $e->getMessage());
            return;
        }

        $this->fail("Expected exception, but never raised.");
    }

    public function testSetNoFileDirectoryNotWritable()
    {
        $dir = self::DIR . "/xxx";
        mkdir($dir);
        chmod($dir, 000);

        $file = $dir . "/test.sync";

        $sync = new Sync($file);
        try
        {
            $sync->test = "fail";
        }
        catch (\Exception $e)
        {
            $expected = sprintf("Directory '%s' does not exist or is not writable.", $dir);
            $this->assertEquals($expected, $e->getMessage());
            chmod($dir, 770);
            return;
        }

        chmod($dir, 770);
        $this->fail("Expected exception, but never raised.");
    }

    public function testSetFileExistsButNotWritable()
    {
        $file = self::DIR . "/test.sync";

        $fd = fopen($file, 'w');
        fclose($fd);
        chmod($file, 0000);

        $sync = new Sync($file);
        try
        {
            $sync->test = "fail";
        }
        catch (\Exception $e)
        {
            $expected = sprintf("File '%s' is not writable.", $file);
            $this->assertEquals($expected, $e->getMessage());
            return;
        }

        $this->fail("Expected exception, but never raised.");
    }

    public function testSetNoFileButDirectoryWritable()
    {
        $file = self::DIR . "/test.sync";
        $sync = new Sync($file);
        $this->assertFalse(file_exists($file));
        $sync->hello = "world";
        $this->assertTrue(file_exists($file));
        $this->assertEquals("world", $sync->hello);
    }

    public function testSetFileAlreadyExisting()
    {
        $file = self::DIR . "/test.sync";
        $sync = new Sync($file);
        $this->assertFalse(file_exists($file));
        $sync->hello = "world";
        $this->assertTrue(file_exists($file));
        $this->assertEquals("world", $sync->hello);
        $sync->hello = "foo";
        $this->assertEquals("foo", $sync->hello);
    }

}