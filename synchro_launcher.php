<?php

require_once("AbstractProcessesPool.php");
require_once("ProcessesPoolMySQL.php");
require_once("TasksManagerInterface.php");
require_once("TasksManagerMySQL.php");
require_once("Sync.php");

// Removing old synchroized object
if (is_file("/tmp/synchro.txt"))
{
    unlink("/tmp/synchro.txt");
}

// Initializing database connection
$dbh = new PDO("mysql:host=127.0.0.1;dbname=fuz", 'root', 'root');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initializing process pool
$pool = new ProcessesPoolMySQL($label = "synchro pool", $dbh);
$pool->create($max = "10");

// Initializing task manager
$multi = new TasksManagerMySQL($label = "synchro tasks", $dbh);
$multi->destroy();

// Simulating jobs
$todo_list = array ();
for ($i = 1; ($i <= 20); $i++)
{
    $todo_list[$i] = $i;
    $multi->add($todo_list[$i], TasksManagerMySQL::WAITING);
}

// Infinite loop until all jobs are done
$continue = true;
while ($continue)
{
    $continue = false;

    echo "Starting to run jobs in queue ...\n";

    // Shuffle all jobs (else this will be too easy :-))
    shuffle($todo_list);

    // put all failed jobs to WAITING status
    $multi->switchStatus(TasksManagerMySQL::FAILED, TasksManagerMySQL::WAITING);

    foreach ($todo_list as $job)
    {

        $ret = $pool->waitForResource($timeout = 10, $interval = 500000, "waitResource");

        if ($ret)
        {
            echo "Executing job: $job\n";
            exec(sprintf("/usr/bin/php ./synchro_program.php %s > /dev/null &", escapeshellarg($job)));
        }
        else
        {
            echo "waitForResource timeout!\n";
            $pool->killAllResources();

            // All jobs currently running are considered dead, so, failed
            $multi->switchStatus(TasksManagerMySQL::RUNNING, TasksManagerMySQL::FAILED);

            break;
        }
    }

    $ret = $pool->waitForTheEnd($timeout = 10, $interval = 500000, "waitEnd");
    if ($ret == false)
    {
        echo "waitForTheEnd timeout!\n";
        $pool->killAllResources();

        // All jobs currently running are considered dead, so, failed
        $multi->switchStatus(TasksManagerMySQL::RUNNING, TasksManagerMySQL::FAILED);
    }


    echo "All jobs in queue executed, looking for errors...\n";

    // Counts if there is failures
    $multi->switchStatus(TasksManagerMySQL::WAITING, TasksManagerMySQL::FAILED);
    $nb_failed = $multi->countStatus(TasksManagerMySQL::FAILED);
    if ($nb_failed > 0)
    {
        $todo_list = $multi->getCalculsByStatus(TasksManagerMySQL::FAILED);
        echo sprintf("%d jobs failed: %s\n", $nb_failed, implode(', ', $todo_list));
        $continue = true;
    }
}

function waitResource($multi)
{
    echo "Waiting for a resource ....\n";
}

function waitEnd($multi)
{
    echo "Waiting for the end .....\n";
}

// All jobs finished, destroying task manager
$multi->destroy();

// Destroying process pool
$pool->destroy();

// Recovering final result
$shared = new Sync("/tmp/synchro.txt");
echo sprintf("Result of the sum of all numbers between 1 and 20 included is: %d\n", $shared->result20);

echo "Finish.\n";

