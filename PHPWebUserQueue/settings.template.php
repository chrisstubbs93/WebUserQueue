<?php
//rename this file to settings.php

$debug = FALSE; 

$selfdrive = TRUE; //Allow user page requests to self drive the worker in order to keep things running faster

$timeout =3*60; //Time in seconds, how long a session lasts

$noshowtimeout = 2*60; //Time in seconds their place at the front of the queue will be held

$sqlserver = "localhost";
$sql_db = "queuetest";
$sql_user = "queuetest";
$sql_pass = "abab";

setlocale(LC_ALL, 'en_GB');
date_default_timezone_set('UTC');

$nexmokey = "abab";
$nexmosecret = "abab";
?>