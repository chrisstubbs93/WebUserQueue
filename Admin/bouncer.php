<?php
//This is the queue manager for admins
session_start();
if(isset($_GET["autorefresh"])){
    if($_GET["autorefresh"]=="yes"){
    	echo '<head> <meta http-equiv="refresh" content="3"> </head>';
    }
}

include '../PHPWebUserQueue/settings.php';


// Create connection
$conn = new mysqli($sqlserver, $sql_user, $sql_pass, $sql_db);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$conn->query("SET time_zone = 'Europe/London'");

echo "<h1>Queue Admin (BOUNCER)</h1><br />";

//handle kick
if(isset($_GET["kick"]))
{
    $idtokick = $conn->real_escape_string($_GET["kick"]);
    $sql = "UPDATE users SET session_complete=1, status='kicked: bouncer' WHERE id=$idtokick";
    if ($conn->query($sql) === TRUE) {
        echo "User $idtokick kicked successfully <br />";
    } else {
        echo "Error kicking user $idtokick: " . $conn->error;
    }
}


//count people in db
echo "Total users in database: ".($conn->query("SELECT * FROM users")->num_rows)." <br />";
//count people in queue
echo "Total users in queue: ".($conn->query("SELECT * FROM users WHERE session_complete = 0")->num_rows)." <br />";

?>
<br />
<form action="#" method="get">
Filters:<br />
<input type="checkbox" name="filter" value="dispactiveusers" <?php if(isset($_GET["filter"])){if($_GET["filter"]=="dispactiveusers")echo "checked";} ?>> Display only active users (waiting in queue or driving)<br />
<input type="checkbox" name="autorefresh" value="yes" <?php if(isset($_GET["autorefresh"])){if($_GET["autorefresh"]=="yes")echo "checked";} ?>> Autorefresh<br />
<input type="submit" value="Apply">
</form>
<br />
<br />


<table width="100%" border="1" cellspacing="0" cellpadding="1">
  <tr>
    <td><u><b>id</b></u></td>
    <td><u><b>session</b></u></td>
    <td><u><b>name</b></u></td>
    <td><u><b>request_time</b></u></td>
    <td><u><b>access_time</b></u></td>
    <td><u><b>ip</b></u></td>
    <td><u><b>last_ping_time</b></u></td>
    <td><u><b>session_complete</b></u></td>
    <td><u><b>tel</b></u></td>
    <td><u><b>twitter</b></u></td>
    <td><u><b>message_sent_time</b></u></td>
    <td><u><b>time_left</b></u></td>
	<td><u><b>status</b></u></td>
    <td><u><b>Kick?</b></u></td>
    <td><u><b>Takeover?</b></u></td>
  </tr>
  <?php 
  $sql = "SELECT * FROM users";
  if(isset($_GET["filter"])){if($_GET["filter"]=="dispactiveusers") $sql = $sql . " WHERE session_complete = 0";}
  $sql = $sql . " ORDER BY id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
        ?>
  <tr>
    <td><?php echo $row["id"]; ?></td>
    <td><?php echo $row["session"]; ?></td>
    <td><?php echo $row["name"]; ?></td>
    <td><?php echo $row["request_time"]; ?></td>
    <td><?php echo $row["access_time"]; ?></td>
    <td><?php echo $row["ip"]; ?></td>
    <td><?php echo $row["last_ping_time"]; ?></td>
    <td><?php echo $row["session_complete"]; ?></td>
    <td><?php echo $row["tel"]; ?></td>
    <td><?php echo $row["twitter"]; ?></td>
    <td><?php echo $row["message_sent_time"]; ?></td>
	
    <td>
    <?php
    //determine how long the person at the front of the queue has left
    $sql2 = "SELECT * FROM users WHERE session_complete = 0 AND id = '" . $row["id"] . "' ORDER BY id ASC LIMIT 1";
    $result2 = $conn->query($sql2);
    if ($result2->num_rows == 1) {
    while($row2 = $result2->fetch_assoc()) {
        $timeatfront = ($timeout-(strtotime("now")-strtotime($row2["access_time"])));
         echo $timeatfront;
    }
    }
    ?>  
    </td>
	
	<td><?php echo $row["status"]; ?></td>
    <td><input type="button" value="Kick" onclick="window.location.href='bouncer.php?kick=<?php echo $row["id"]; ?>'" /></td>
    <td><input type="button" value="Takeover" onclick="window.location.href='<?php echo $sitepath;?>/?i=<?php echo $row["id"]; ?>&s=<?php echo $row["session"]; ?>'" /></td>
  </tr>
  <?php
        }
    } else {
    echo "0 results";
    }
    ?>
</table>


<?php
$conn->close();
?>