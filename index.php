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

$data = $database->select("fbData", "*");

print_r($data);
