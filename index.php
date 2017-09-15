<?php
//Includes
require_once ("inc/config.php");
require_once ("vendor/autoload.php");

//Init Database
$database = new \Medoo\Medoo(
    array(
        "database_type" => "mysql",
        "database_name" => $dbName,
        "server" => $dbHost,
        "username" => $dbUsername,
        "password" => $dbPassword
    )
);

$data = $database->select("posts", "*");

print_r($data);
///"https://www.facebook.com/permalink.php?id=".$id[0]."&v=wall&story_fbid=".$id[1],