<?php
//Boekenjagers FB GROUP ID
define("BOEKENJAGERS_GROUPID", 173371763090905);

//Includes
require_once ("inc/config.php");
require_once ("vendor/autoload.php");

//Init Facebook API
$fb = new Facebook\Facebook([
    "app_id" => $appID,
    "app_secret" => $appSecret,
    "default_graph_version" => "v2.10",
]);

$fb->setDefaultAccessToken($fb->getApp()->getAccessToken());

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

//Request feed from facebook
$request = $fb->get("/".BOEKENJAGERS_GROUPID."/feed");
$graphEdge = $request->getGraphEdge();

//Load data from gemeentes
$dbData = $database->select("gemeentes", array("zipcode", "name"));

//Loop through the posts
foreach($graphEdge->all() as $graphNode) {
    $postData = $graphNode->all();

    //Loop through gemeentes
    foreach($dbData as $row){
        if( preg_match("~\b".strtolower($row["name"])."\b~", strtolower($postData["message"])) > 0) {
            $id = explode("_", $postData["id"]);

            $count = $database->count("posts", array("postID" => $id[1]));

            if ($count == 0) {
                $database->insert("posts", array(
                    "zipcode" => $row["zipcode"],
                    "postID" => $id[1],
                    "time" => date("Y-m-d H:i:s", $postData["updated_time"]->getTimestamp()),
                    "text" => $postData["message"]
                ));
            }

            break;
        }
    }
}