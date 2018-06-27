<?php

interface TasksManagerInterface
{

    public function getClusterLabel();
    public function getCalculLabel();

    public function destroy();

    public function start($calcul_label);
    public function finish($status);

    public function add($calcul_label, $status);
    public function delete($calcul_label);

    public function countStatus($status);
    public function count();

    public function getCalculsByStatus($status);
    public function switchStatus($statusA, $statusB);

}