<?php

//This is the worker script. It runs on a regular cron or can be self driven faster if required

//handles background tasks like:
// +clearing timed out users 
// +sending sms
// +clearing noshows

include 'settings.php';

if(!isset($_SESSION)) 
{ 
    session_start(); 
} 

//if($thisIsASelfDrive) $debug = false; //surpress user output if it's a self drive

// Create connection
$conn = new mysqli($sqlserver, $sql_user, $sql_pass, $sql_db);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$conn->query("SET time_zone = 'Europe/London'");
//$conn->query("SET time_zone = '+01:00'");


/////////////////////////////////////////////////////////////
//Check if the first person in the queue's session has expired
$kickbool = FALSE;
$sql = "SELECT * FROM users WHERE session_complete = 0 ORDER BY id ASC LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows == 1) {
    while($row = $result->fetch_assoc()) {
        $accesstime=$row["access_time"];
        $idtokick=$row["id"];
        if ($debug == TRUE) echo "User $idtokick found with a session started at $accesstime <br />";
        $kickbool = ((strtotime("now")-strtotime($accesstime)>$timeout) and (strtotime($accesstime)>0) and isset($accesstime));
    }
}

//if it has expired, kick them
if ($kickbool){
    if ($debug == TRUE) echo "User $idtokick session expired. ElapsedTime:" . (strtotime("now")-strtotime($accesstime)) . " seconds<br />";
    
            $sql = "UPDATE users SET session_complete=1, status='kicked: timeup' WHERE id = '" .$idtokick."'";
        if ($conn->query($sql) === TRUE) {
            if ($debug == TRUE)echo "User $idtokick kicked successfully<br />";
            } else {
                echo "Error kicking user: " . $conn->error;
            }
}
else 
{   
    if ($debug == TRUE) echo "Nobody to kick!<br />";
}

/////////////////////////////////////////////////////////////
//send a message to the next user in line
$sql = "SELECT * FROM users WHERE session_complete = 0 ORDER BY id ASC LIMIT 1";
$result = $conn->query($sql);

$telNumber="";
$usertomessage;
$messageSentTime;
$smsId;
$smsSession;

if ($result->num_rows == 1) {
    while($row = $result->fetch_assoc()) {
        $messageSentTime = $row["message_sent_time"];
        if(is_null($messageSentTime))
        {
            $usertomessage = $row["id"];
            if ($debug == TRUE) echo "Notifying user ID $usertomessage that it's their turn<br />";
            if ($row["tel"] != "")
            {
                $telNumber = $row["tel"];
                $smsId = $row["id"];
                $smsSession = $row["session"];		
            }              
        }
    }
    if(is_null($messageSentTime))
    {
        if($telNumber != "")
        {
            //Send nexmo SMS
            if ($debug == TRUE) echo "sending text";

            $url = 'https://rest.nexmo.com/sms/json';

            //The data you want to send via POST
            $data = '{"api_key": "' . $nexmokey . '", "api_secret": "' . $nexmosecret . '", "from": "EMFRoamer", "to": "' . $telNumber . '", "text": "Your turn! Click here to control the EMF Roamer https://roamer.fun/?i='. $smsId . '&s=' . $smsSession . '" }';


            //open connection
            $ch = curl_init();

            $headers = array(
                "Content-Type: application/json",
                "Accept: application/json",
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $data);

            //So that curl_exec returns the contents of the cURL; rather than echoing it
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

            //execute post
            $result = curl_exec($ch);
            if ($debug == TRUE) echo $result;

            $sql = "UPDATE users SET status='text sent: $result' WHERE id=$usertomessage";
            if ($conn->query($sql) === TRUE) {
                if ($debug == TRUE) echo "status updated successfully";
            } else {
                echo "Error updating status: " . $conn->error;
            }
        }
        //set the message_sent_time to now, even if we have't sent them a message to make the noshow work.
        $sql = "UPDATE users SET message_sent_time = now() WHERE id=$usertomessage";
        if ($conn->query($sql) === TRUE) {
            if ($debug == TRUE) echo "message_sent_time updated successfully";
        } else {
            echo "Error updating message_sent_time: " . $conn->error;
        }
    }
}
    
 /////////////////////////////////////////////////////////////
//check if the person at the front of the queue hasn't taken their turn yet.
$sql = "SELECT * FROM users WHERE session_complete = 0 AND access_time IS NULL ORDER BY id ASC LIMIT 1";
$result = $conn->query($sql);
$noshowbool = false;
if ($result->num_rows == 1) {
    while($row = $result->fetch_assoc()) {
        if(!is_null($row["message_sent_time"]))
        {
            //they have been notified their turn is ready
            $noshowid = $row["id"];
            $messagesenttime = $row["message_sent_time"];
            if ($debug == TRUE) echo "User $noshowid has been notified that their turn is ready (according to the database at least)<br />";
            
            //Are they a noshow?
            $noshowbool = ((strtotime("now")-strtotime($messagesenttime)>$noshowtimeout) and (strtotime($messagesenttime)>0) and isset($messagesenttime));
        }
    }
    if ($noshowbool == TRUE)
    {
        //they are a noshow, kick $noshowid.
        if ($debug == TRUE) echo "User $noshowid is a noshow<br />";
        $sql = "UPDATE users SET session_complete=1, status='kicked: no-show' WHERE id = '" .$noshowid."'";
        if ($conn->query($sql) === TRUE){
            if ($debug == TRUE)echo "Noshow user $noshowid kicked successfully<br />";
            } else {
                echo "Error kicking noshow user $noshowid: " . $conn->error;
            }
    }
}
?>