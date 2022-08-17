<?php
//The queue is “self driven” whenever a page is accessed which calls the Web User Queue library.
//A technique borrowed from “wp-cron”, how WordPress handles tasks.
//It is recommended that you also set your server up to periodically run the cron job, to keep things ticking over and send SMS, even if all users have closed the page. 

//Disable any echo output for user page requests to self drive the worker.
include 'settings.php';
if ($selfdrive){
    $thisIsASelfDrive = TRUE;
    include 'worker.php';
}
?>