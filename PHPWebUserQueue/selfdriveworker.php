<?php
//horrible bodge for user page requests to self drive the worker, but disable any echo output.
include 'settings.php';
if ($selfdrive){
    $thisIsASelfDrive = TRUE;
    include 'worker.php';
}
?>