<?php
//Setup script for queue system

include 'settings.php';

// Create connection
$conn = new mysqli($sqlserver, $sql_user, $sql_pass, $sql_db);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql="
CREATE TABLE `users` (
  `id` int(20) NOT NULL,
  `session` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `request_time` datetime DEFAULT NULL,
  `access_time` datetime DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_ping_time` datetime NOT NULL,
  `session_complete` tinyint(1) NOT NULL DEFAULT '0',
  `tel` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `twitter` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `message_sent_time` datetime DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `status` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";
if ($conn->query($sql) === TRUE) {
                if ($debug == TRUE)echo "Queue users table created successfully <br />";
            } else {
                echo "<br />Error creating tables: " . $conn->error;
            }




$sql="
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);
";
if ($conn->query($sql) === TRUE) {
                if ($debug == TRUE)echo "Primary key set successfully <br />";
            } else {
                echo "<br />Error setting primary key: " . $conn->error;
            }



$sql="
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;
";
if ($conn->query($sql) === TRUE) {
                if ($debug == TRUE)echo "Set auto increment successfully <br />";
            } else {
                echo "<br />Error setting auto increment: " . $conn->error;
            }


echo "<br />Setup script done!"; 
echo "<br /><a href='index.php'> Click here to go to the queue system!</a>"; 

?>