
# WebUserQueue
Introduction
------------
This is a web user queueing system written in PHP with a MySQL backend designed to run on shared hosting. 

It was designed to allow one user at a time to control of the EMF Roamer, an internet controlled robot.

If users provide a phone number and close the page after joining the queue, they will be sent an SMS using Vonage when it's their turn.

Installation
------------

To make full use of the SMS delivery, you'll need to create a Vonage account.

To install the PHP library to your project, copy the PHPWebUserQueue to your server and rename `settings.template.php` to `settings.php`, then edit it:
```php
$selfdrive = TRUE; //Allow user page requests to self drive the worker in order to keep things running faster
$timeout =3*60; //Time in seconds, how long a session lasts
$noshowtimeout = 2*60; //Time in seconds their place at the front of the queue will be held
$sqlserver = "localhost";
$sql_db = "queuetest";
$sql_user = "queuetest";
$sql_pass = "abab";
$nexmokey = "abab";
$nexmosecret = "abab";
```
Create a database on your MySQL server with an associated user, then run the setup script:
```
http://[your site]/PHPWebUserQueue/setup.php
```

Usage
-----

Include the library file:

```php
include '../PHPWebUserQueue/WebUserQueue.php';
```

Examples
--------