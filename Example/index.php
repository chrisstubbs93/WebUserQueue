<?php
//This is the landing page for the queue.
//Users should come here to get a session.
//If users already have a session, they will stay here in the queue.
//If users have a session and are at the front of the queue, they will be auto sent to the control page.
//Users can land here from SMS links

include '../PHPWebUserQueue/WebUserQueue.php';
$q = new WebUserQueue();

//get any parameters passed back from the sms link
if (isset($_GET["i"]) and isset($_GET["s"]))
{
    $_SESSION["i"] = $_GET["i"];
    $_SESSION["s"] = $_GET["s"];
}

//check if the user has a session already. If not, give them a session and put them in the DB
if (!isset($_SESSION["i"])){ 
	//User is not already in the queue.
	if(!isset($_POST['name'])){
		//User has not posted the form, so show them the form.
		echo "<form method=\"post\"><input type=\"text\" name=\"name\" placeholder=\"Name\"> (optional)<br />";
		echo "<input type=\"text\" name=\"phone\" placeholder=\"Phone # e.g. 07712345678\"> (optional)<br />";
		echo "We can send you an SMS when it's your turn.<br /><button>Put me in the queue</button></form>";
	}
	else{
		//user has posted the form, add them to the queue.
		//need to check here if (onsiteipcheck OR accesskey correct) is true
		if($q->join($_POST["name"],$_POST["phone"])){
			echo "<head><meta http-equiv=\"refresh\" content=\"2\" ></head>";
            echo "Welcome to the queue, your ID is: " . $_SESSION["i"] . " <br />";
		}
	}
}
else{//User is in the queue
	if($q->checkValid($_SESSION["i"],$_SESSION["s"])) { //Their session matches the database
		if(isset($_GET["end"])){ //Request to end their session
			if($q->kick($_SESSION["i"],$_SESSION["s"])){
        		echo "Thank you for queueing.<br /><a href='index.php'>Click here to enter the queue again</a>";
    		}
		}
		else{
	        list($queuePos,$waitTime) = $q->checkWait($_SESSION["i"],$_SESSION["s"]);
	        if ($queuePos == 0){ //It's their turn
	            header("Location: control.php");
	        }
	        else{ //They're waiting
	            echo "<head><meta http-equiv=\"refresh\" content=\"5\" ></head>";
	            echo "There are " . $queuePos . " people in front of you. This will take about ". $waitTime . "  seconds.<br />";
	        }
    	}
	} else { //Session is not valid
	    echo "Your session has expired. Please refresh the page to queue again!";
	    unset($_SESSION['i']);
	    unset($_SESSION['s']);
	}
}
?>