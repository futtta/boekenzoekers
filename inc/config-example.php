<?php
//MySQL Database

//Database host
$dbHost = "localhost";
//Database naam
$dbName = "boekenzoekers";
//Database username
$dbUsername = "boekenzoekers";
//Database password
$dbPassword = "databasepassword";

//FB Developer
//https://developers.facebook.com/

//App ID
$appID = "";
//App Secret
$appSecret = "";

//How many days before posts may be deleted from the database
$daysBeforeDelete = 90;

//Boekenjagers GroupID
$fbGroupID = 0;

//blacklist words
$blacklist = array();

//Login om posts te deleten
$login = array(
    "username" => "password"
);

//cross domain requests OK voor domain
$corsDomains = "http://www.boeken-jagers.be";
