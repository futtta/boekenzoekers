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
$dbData = $database->select("gemeentes", array("postcode", "naam"));

//Loop through the posts
foreach($graphEdge->all() as $graphNode) {
    $postData = $graphNode->all();
    //Loop throught gemeentes
    foreach($dbData as $row){
        if( preg_match("~\b".strtolower($row["naam"])."\b~", strtolower($postData["message"])) > 0) {
            $id = explode("_", $postData["id"]);

            $database->insert("fbData", array(
                "locatie" => $row["postcode"],
                "fbURL" => "https://www.facebook.com/permalink.php?id=".$id[0]."&v=wall&story_fbid=".$id[1],
                "fbTime" => date("Y-m-d H:i:s",$postData["updated_time"]->getTimestamp()),
                "fbTekst" => $postData["message"]
            ));
            break;
        }
    }
}