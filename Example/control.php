<?php 
//This is the control page.
//Users should only be here if they are at the front of the queue.
//Invalid/no sessions will be sent back to the queue.
//(not implemented yet) If users session expires while using the control page, and nohody else is in the queue, they will be able to stay here (their session will not be terminated yet).
//If users session expires while using the control page, and someone else is in the queue, their session will be terminated with a message.


include '../PHPWebUserQueue/WebUserQueue.php';
$q = new WebUserQueue();

//User already has a session. Check it's not already used, all their details are valid, and they are first in the queue.
if (isset($_SESSION["i"]))
{
    if($q->checkFirst($_SESSION["i"],$_SESSION["s"])){

    } else {
        echo "<a href='index.php'>Your session is not valid or has expired. Please click here to join the queue</a>";
        die();
    }
} else {
    header("Location: index.php");
    echo "<a href='index.php'>Your session is not valid. Please go back to the home page</a>";
    die();
}

?>
<html>
  <head>
    <title>Front of queue</title>
    <script>
      var secondsleft = <?php echo $q->checkRemaining($_SESSION["i"],$_SESSION["s"]);?>;
      var countdowncaller = setInterval(function(){countdown()}, 1000);//Update the countdown every second.
      function countdown() {
            secondsleft = secondsleft - 1;
            document.getElementById('timeleft').innerHTML = secondsleft + " seconds remaining.";
            if (secondsleft < 10) {
              document.body.style.backgroundColor = "red";//warn them
            }
            if (secondsleft <= 0) {
              window.location.replace("index.php?end");//kick them
            }
          }
    </script>
  </head>
  <body>
    You are at the front of the queue! <br />
    <div id="timeleft">Loading...</div>
    <button onclick="window.location.href='index.php?end'">End session</button>
  </body>
</html>