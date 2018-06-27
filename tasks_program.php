<?php

if (!isset($argv[1]))
{
    die("This program must be called with an identifier (calcul_label)\n");
}
$calcul_label = $argv[1];

require_once("AbstractProcessesPool.php");
require_once("ProcessesPoolMySQL.php");
require_once("TasksManagerInterface.php");
require_once("TasksManagerMySQL.php");

// Initializing database connection
$dbh = new PDO("mysql:host=127.0.0.1;dbname=fuz", 'root', 'root');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initializing process pool (with same label as parent)
$pool = new ProcessesPoolMySQL($label = "pool test", $dbh);

// Takes one resource in pool
$pool->start();

// Initializing task manager (with same label as parent)
$multi = new TasksManagerMySQL($label = "jobs test", $dbh);
$multi->start($calcul_label);

// Simulating execution time
$secs = (rand() % 2) + 3;
sleep($secs);

// Simulating job status
$status = rand() % 3 == 0 ? TasksManagerMySQL::FAILED : TasksManagerMySQL::SUCCESS;

// Job finishes indicating his status
$multi->finish($status);

// Releasing pool's resource
$pool->finish();

