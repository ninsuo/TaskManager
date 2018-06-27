<?php

// pool_mysql_launcher.php

require_once("AbstractProcessesPool.php");
require_once("ProcessesPoolMySQL.php");

$dbh = new PDO("mysql:host=127.0.0.1;dbname=fuz", 'root', 'root');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$multi = new ProcessesPoolMySQL($label = 'test', $dbh);

if ($multi->create($max = '10') == false)
{
    echo "Pool creation failed ...\n";
    exit();
}

$count = 20;

for ($i = 0; ($i < $count); $i++)
{
    $ret = $multi->waitForResource($timeout = 10, $interval = 500000, 'test_waitForResource');
    if ($ret)
    {
        echo "Execute new process: $i\n";
        exec("/usr/bin/php ./pool_mysql_calc.php $i > /dev/null &");
    }
    else
    {
        echo "WaitForResources Timeout! Killing zombies...\n";
        $multi->killAllResources();
        break;
    }
}

$ret = $multi->waitForTheEnd($timeout = 10, $interval = 500000, 'test_waitForTheEnd');
if ($ret == false)
{
    echo "WaitForTheEnd Timeout! Killing zombies...\n";
    $multi->killAllResources();
}

$multi->destroy();
echo "Finish.\n";

function test_waitForResource($multi)
{
    echo "Waiting for available resource ( {$multi->getLabel()} )...\n";
}

function test_waitForTheEnd($multi)
{
    echo "Waiting for all resources to finish ( {$multi->getLabel()} )...\n";
}
