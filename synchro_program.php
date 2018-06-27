<?php

if (!isset($argv[1]))
{
    die("This program must be called with an identifier (calcul_label)\n");
}
$current_id = $argv[1];

require_once("AbstractProcessesPool.php");
require_once("ProcessesPoolMySQL.php");
require_once("TasksManagerInterface.php");
require_once("TasksManagerMySQL.php");
require_once("Sync.php");

// Initializing database connection
$dbh = new PDO("mysql:host=127.0.0.1;dbname=fuz", 'root', 'root');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initializing process pool (with same label as parent)
$pool = new ProcessesPoolMySQL($label = "synchro pool", $dbh);

// Takes one resource in pool
$pool->start();

// Initializing task manager (with same label as parent)
$multi = new TasksManagerMySQL($label = "synchro tasks", $dbh);
$multi->start($current_id);

// ------------------------------------------------------
// Job begins here

$synchro = new Sync("/tmp/synchro.txt");

if ($current_id == 1)
{
    $synchro->result1 = 1;
    $status = TasksManagerMySQL::SUCCESS;
}
else
{
    $previous_id = $current_id - 1;
    if (is_null($synchro->{"result{$previous_id}"}))
    {
        $status = TasksManagerMySQL::FAILED;
    }
    else
    {
        $synchro->{"result{$current_id}"} = $synchro->{"result{$previous_id}"} + $current_id;
        $status = TasksManagerMySQL::SUCCESS;
    }
}

// ------------------------------------------------------

// Job finishes indicating his status
$multi->finish($status);

// Releasing pool's resource
$pool->finish();

