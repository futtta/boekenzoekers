<?php

define("BOEKENJAGERS_GROUPID", 173371763090905);


require_once ("inc/config.php");
require_once ("vendor/autoload.php");

$fb = new Facebook\Facebook([
    "app_id" => $appID,
    "app_secret" => $appSecret,
    "default_graph_version" => "v2.10",
]);

$fb->setDefaultAccessToken($fb->getApp()->getAccessToken());

$request = $fb->get("/".BOEKENJAGERS_GROUPID."/feed");
$graphEdge = $request->getGraphEdge();

foreach($graphEdge->all() as $graphNode) {
    print_r($graphNode->all());
}


/*
 * FB URL
https://www.facebook.com/permalink.php?id=<USERID>&v=wall&story_fbid=<POSTID>
 */