<?php
//include 'settings.php';
require_once __DIR__ . '/settings.php';
if(!isset($_SESSION)){ 
    session_start(); 
}
require_once __DIR__ . '/selfdriveworker.php';
//include 'selfdriveworker.php';

class WebUserQueue{
    public $conn;

    public function __construct(){
        // Create connection
        include 'settings.php';
        $this->conn = new mysqli($sqlserver, $sql_user, $sql_pass, $sql_db);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        } 
        $this->conn->query("SET time_zone = '+00:00'");
    }

    public function __destruct(){
        // Close connection
        $this->conn->close();
    }

    function join($name, $phone){
        include 'settings.php';
        $s = substr(md5(uniqid()),0,10); //give the user a random ID and store it in their browser
        $_SESSION["s"] = $s;
        $username = $this->conn->real_escape_string($name);
        
        //remove non numerics from tel no
        $cleanTel = preg_replace("/[^0-9]/", "", $this->conn->real_escape_string($phone));
        if ($debug == TRUE)echo "Clean Number $cleanTel";
        //change 07xxx numbers to 447xxx
        if(substr($cleanTel, 0, 2) == "07"){
            if ($debug == TRUE) echo "07 detected";
            $cleanTel = "44" . substr($cleanTel, 1);
            if ($debug == TRUE) echo "Clean international Number $cleanTel";
        }
        
        //check if the queue is empty
        $sql = "SELECT * FROM users WHERE session_complete = 0";
        $result = $this->conn->query($sql);
        if ($result->num_rows == 0) {
            $naccess_time = "now()";
        }
        else{
            $naccess_time = "NULL";
        }

        //put new user in DB
        $sql = "INSERT INTO users (session, request_time, ip, last_ping_time,tel,name, access_time)
        VALUES ('".$s."', now(), '" . $_SERVER['REMOTE_ADDR'] . "', now(),'$cleanTel','$username'," . $naccess_time . ")";
        if ($this->conn->query($sql) === TRUE) {
            $_SESSION["i"] = $this->conn->insert_id;
            return true;
        } else {
            echo "Error: " . $sql . "<br>" . $this->conn->error;
            return false;
        }
    }

    function checkValid($ID, $session){
        include 'settings.php';
        //Check the users session is valid
        $myID = $this->conn->real_escape_string($ID);
        $mySession = $this->conn->real_escape_string($session);
        $sql = "SELECT * FROM users WHERE session_complete = 0 AND id = '". $myID . "' AND session = '". $mySession . "'";
        $result = $this->conn->query($sql);
        //check the user has 1 entry
        if ($result->num_rows == 1) {
            return true; //valid
        } else {
            return false; //invalid
        }
    }

    function checkWait($ID, $session){
        include 'settings.php';
        $ID = $this->conn->real_escape_string($ID);

        $result = $this->conn->query("SELECT * FROM users WHERE session_complete = 0 AND id < '". $ID . "' ORDER BY id ASC");
        $queuePos = $result->num_rows;
        //$waitTime = $queuePos*$timeout; //not very accurate

        //determine how long the person at the front of the queue has left
        $sql = "SELECT * FROM users WHERE session_complete = 0 ORDER BY id ASC LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result->num_rows == 1) {
            while($row = $result->fetch_assoc()) {
                if($row["access_time"] != NULL)
                {
                    $timeatfront = ($timeout-(strtotime("now")-strtotime($row["access_time"])));
                } else {
                    $timeatfront = $timeout;
                }
                $waitTime = $timeatfront + (($queuePos-1)*$timeout);
            }
        }
        else {
            $waitTime = 9999;
        }
        //update the ping on the server
        $sql = "UPDATE users SET last_ping_time=now() WHERE id = '".$ID."' AND session = '".$session."'";
        if ($this->conn->query($sql) === TRUE) {
            if ($debug == TRUE)echo "Ping record updated successfully";
        } else {
            echo "Error updating ping record: " . $conn->error;
        }
        return [$queuePos,$waitTime];
    }

    function checkFirst($ID, $session){
        include 'settings.php';
        $firstvalid = FALSE;
        //User already has a session. Check it's not already used, valid, and they are first in the queue.
        $sql = "SELECT * FROM users WHERE session_complete = 0 ORDER BY id ASC LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result->num_rows == 1) {
            while($row = $result->fetch_assoc()) {
                if($row["id"] == $ID and $row["session"] == $session){
                    //the user is first and valid
                    $firstvalid = TRUE;
                    $access_time = $row["access_time"];
                    $checkedid = $row["id"];
                    $checkedsession = $row["session"];
                    if (is_null($row["access_time"])){$accessed=FALSE;}
                    else{$accessed=TRUE;}
                    if ($debug == TRUE)echo "Your session is valid. ID: $checkedid Session: $checkedsession <br />";
                }
            }
            if($firstvalid){
                if ($debug == TRUE)echo "Accessed: $accessed <br />";
                //update the access time on the server only once
                if($accessed == FALSE){
                    $sql = "UPDATE users SET access_time=now() WHERE id = '" .$ID."' AND session = '".$session."'";
                    if ($this->conn->query($sql) === TRUE) {
                        if ($debug == TRUE)echo "Access time record updated successfully";
                    } else {
                        echo "Error updating access record: " . $conn->error;
                    }
                }
                return true;//the user has 1 entry that is valid and not complete yet
            } else {
            return false;//the user does not have 1 entry that is valid and not complete yet
            }
        }
    }

    function checkRemaining($ID, $session){
        include 'settings.php';
        $ID = $this->conn->real_escape_string($ID);

        $result = $this->conn->query("SELECT * FROM users WHERE session_complete = 0 AND id < '". $ID . "' ORDER BY id ASC");
        $queuePos = $result->num_rows;
        //$waitTime = $queuePos*$timeout; //not very accurate

        //determine how long the person at the front of the queue has left
        $sql = "SELECT * FROM users WHERE session_complete = 0 ORDER BY id ASC LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result->num_rows == 1) {
            while($row = $result->fetch_assoc()) {
                if($row["access_time"] != NULL)
                {
                    $timeatfront = ($timeout-(strtotime("now")-strtotime($row["access_time"])));
                } else {
                    $timeatfront = $timeout;
                }
                return ($timeatfront + (($queuePos)*$timeout));
            }
        }
        else {
            return 9999;
        }
    }

    function kick($ID, $session){
        $idtokick = $this->conn->real_escape_string($_SESSION["i"]);
        $sessiontokick = $this->conn->real_escape_string($_SESSION["s"]);

        unset($_SESSION['i']);
        unset($_SESSION['s']);

        $sql = "UPDATE users SET session_complete=1 WHERE id='".$idtokick."' AND session='".$sessiontokick."'";
        if ($this->conn->query($sql) === TRUE) {
            return true;
        } else {
            echo "Error kicking user $idtokick: " . $conn->error;
            return false;
        }
    }

}
?>