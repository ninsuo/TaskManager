<?php

// pool_files_calc.php

require_once("AbstractProcessesPool.php");
require_once("ProcessesPoolFiles.php");

// we create the *same* instance of the process pool
$multi = new ProcessesPoolFiles($label = 'test', $dir = "/tmp");

// child tells the pool it started (there will be one more resource busy in pool)
$multi->start();

// here I simulate job's execution
sleep(rand() % 7 + 1);

// child tells the pool it finished his job (there will be one more resource free in pool)
$multi->finish();

