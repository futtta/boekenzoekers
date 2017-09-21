<?php
date_default_timezone_set("Europe/Brussels");

//Includes
require_once("inc/config.php");
require_once("vendor/autoload.php");

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

//Get max date from posts-table
$lastDate = strtotime($database->max("posts","time"));

//Request feed from facebook
$data = fetchUrl("https://graph.facebook.com/" . $fbGroupID . "/feed?limit=100&since=" . $lastDate . "&access_token=" . $appID . "|" . $appSecret);
$data = json_decode($data, true)["data"];

//Load data from gemeentes
$dbData = $database->select("gemeentes", array("zipcode", "name"));

//Loop through the posts
foreach ($data as $postData) {
    //Loop through gemeentes
    foreach ($dbData as $row) {
        //No Post message = no text = break;
        if (!isset($postData["message"])) {
            break;
        }

        if (strpos($row["name"], "-") !== false) {
            $row["regex"] = str_replace("-", "[-|\s]", $row["name"]);
        }

        if (strpos(strtolower($row["name"]), "sint") !== false) {
            if (!isset($row["regex"])) {
                $row["regex"] = str_replace("sint", "(st\.?|sint)", $row["name"]);
            } else {
                $row["regex"] = str_replace("sint", "(st\.?|sint)", $row["regex"]);
            }
        }

        if (!isset($row["regex"])) {
            $row["regex"] = $row["name"];
        }

        checkNameinText($row, $postData);
    }
}

deleteOldPosts();
/**
 * Delete old posts from the database
 */
function deleteOldPosts()
{
    global $daysBeforeDelete, $database;

    $database->delete("posts", array("[<]time" => date("Y-m-d H:i:s", strtotime("-" . $daysBeforeDelete . " days"))));
}

/**
 * Check if the city name is in the text and insert or update the database
 * @param $cityData array CityData from database
 * @param $postData array PostData from facebook
 */
function checkNameinText($cityData, $postData)
{
    global $database, $blacklist;

    if ( preg_match("~\b" . strtolower($cityData["regex"]) . "\b~", strtolower($postData["message"])) > 0 ) {
        if ( !is_array($blacklist) || $postData["message"] === str_replace( $blacklist, "", $postData["message"] ) ) {
            $id = explode("_", $postData["id"]);

            $count = $database->count("posts", array("postID" => $id[1], "gemeente" => $cityData["name"], "zipcode" => $cityData["zipcode"]));

            if ($count == 0) {
                $database->insert("posts", array(
                    "zipcode" => $cityData["zipcode"],
                    "gemeente" => $cityData["name"],
                    "postID" => $id[1],
                    "time" => date("Y-m-d H:i:s", strtotime($postData["updated_time"])),
                    "text" => $postData["message"]
                ));
            } else {
                $database->update("posts",
                    array("text" => $postData["message"], "time" => date("Y-m-d H:i:s", strtotime($postData["updated_time"]))),
                    array("postID" => $id[1], "gemeente" => $cityData["name"], "zipcode" => $cityData["zipcode"])
                );
            }
        }
    }
}

/**
 * Get the data from the URL using CURL
 * @param $url String url
 * @return mixed Data
 */
function fetchUrl($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;
}
