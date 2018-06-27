<?php

/*
 * This demo demonstrates that the same instance of the Sync class
 * is used, even if two php programs use it.
 *
 * Should be run using PHP Cli
 */

require("Sync.php");

$sync = new Sync("/tmp/demo.sync");

if (isset($argv[1]) === false)
{
    // master process (the one you launched)
    $sync->hello = "foo, bar!\n";

    exec(sprintf("/usr/bin/php %s demo", escapeshellarg($argv[0])));

    echo $sync->hello;
}
else
{
    // child process
    $sync->hello = "Hello, world!\n";
}


