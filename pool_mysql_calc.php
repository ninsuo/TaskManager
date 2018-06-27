<?php

// pool_mysql_calc.php

require_once("AbstractProcessesPool.php");
require_once("ProcessesPoolMySQL.php");

$dbh = new PDO("mysql:host=127.0.0.1;dbname=fuz", 'root', 'root');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$multi = new ProcessesPoolMySQL($label = 'test', $dbh);

$multi->start();

// here I simulate job's execution
sleep(rand() % 7 + 1);

$multi->finish();
