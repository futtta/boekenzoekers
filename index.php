<?php
date_default_timezone_set("Europe/Brussels");
header("Access-Control-Allow-Origin: *");

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

if (isLoggedIn() && array_key_exists("delete", $_GET) && is_numeric($_GET["delete"])) {
    $database->delete("posts", array("id" => $_GET["delete"]));
    print("<b>Succesvol post #" . $_GET["delete"] . " verwijderd</b>");
}


if (array_key_exists("feed",$_GET) && $_GET["feed"]==="rss") {
    outputFeed();
} else {
    outputHTML();
}

function outputFeed() {
    global $fbGroupID;

    $feed = new \Zelenin\Feed;
    $feed->addChannel("http://".$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]."?feed=rss");

    $feed
        ->addChannelTitle('Boekenjagers zoekresultaten voor "'.ucfirst(getGemeente()).'"')
        ->addChannelLink('http://boeken-jagers.be/');
        
    $data=getPostsFromDB();

    if(!empty($data)) {
        foreach ($data as $row) {
            $feed->addItem();
            $feed
                ->addItemTitle(htmlentities(shortenText($row["text"])))
                ->addItemLink("https://www.facebook.com/permalink.php?id=" . $fbGroupID . "&v=wall&story_fbid=" . $row["postID"])
                ->addItemPubDate($row["time"])
                ->addItemDescription(htmlentities($row["text"]));
        }
    }
    
    echo $feed;
}

function outputHTML() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Boekenzoekers</title>
        <link rel="stylesheet" type="text/css" href="main.css">
        <meta name="viewport" content="width=device-width, initial-scale=1" />
    </head>
    <body>

    <form action="">
        <p>
            <label for="gemeente" class="desktop">Gemeente/Postcode:</label>&nbsp;
            <input type="text" id="gemeente" name="gemeente" placeholder="Postcode of Gemeentenaam" value="<?php echo getGemeente(); ?>">&nbsp;
            <input type="submit" value="Zoek">
        </p>
    </form>

    <table id="result">
        <tr>
            <th class="desktop">Postcode</th>
            <th class="desktop">Gemeente</th>
            <th>Tekst</th>
            <th>Post Datum</th>
        </tr>
        <?php drawPosts(); ?>
    </table>

    </body>
    </html>
    <?php
}

/**
 * Shorten text to 100 letters
 * @param $text String input text
 * @return String shortened text text
 */
function shortenText($text)
{
    if (strlen($text) < 150) {
        return $text;
    } else {
        return substr($text, 0, 150) . "...";
    }
}

/**
 * Search function for database
 * @return array Search Parameterw
 */
function getSearch()
{
    $search = array();

    if (isset($_GET["gemeente"])) {
        $zipcode = $_GET["gemeente"];

        if (preg_match("/(st\.?|sint)[-|\s]([A-z]*)/i", $zipcode, $matches)) {
            $zipcode = "sint-".$matches[2];
        }

        if (is_numeric($zipcode)) {
            $search["zipcode"] = $zipcode;
        } else {
            $search["gemeente[~]"] = $zipcode;
        }
    }

    return $search;
}

/**
 * Get Limit parameters in case search is not empty
 * @param &$search
 * @return array
 */
function getLimit(&$search = array())
{
    $search["ORDER"] = array("time" => "DESC");
    $limit = array();

    if (empty($search)) {
        $limit["LIMIT"] = 100;
    }

    return $limit;
}

/**
 * Draw the posts using the database
 */
function drawPosts()
{
    global $fbGroupID;

    $data=getPostsFromDB();

    if(empty($data)) {
        print("<tr>");
        print("<td colspan=\"4\">Geen resultaten gevonden: <b>Keep calm and hide a book!</b></td>");
        print("</tr>");
    } else {
        foreach ($data as $row) {
            print("<tr>");
            print("<td class=\"desktop\">" . $row["zipcode"] . "</td>");
            print("<td class=\"desktop\">" . $row["gemeente"] . "</td>");
            print("<td><a href=\"https://www.facebook.com/permalink.php?id=" . $fbGroupID . "&v=wall&story_fbid=" . $row["postID"] . "\" target=\"_blank\">" . shortenText($row["text"]) . "</a>");

            if (isLoggedIn()) {
                print(" <a href=\"" . generateURL() . "&delete=".$row["id"]."\">[delete]</a>");
            }

            print("</td>");

            print("<td>" . drawTime($row["time"]) . "</td>");
            print("</tr>");
        }
    }
}

function getPostsFromDB()
{
    global $database;

    $search = getSearch();
    $limit = getLimit($search);
    $count = $database->count("posts", "*", $search, $limit);
    if($count == 0) {
        $data = "";
    } else {
        $data = $database->select("posts", "*", $search, $limit);
    }
    
    return $data;
}

/**
 * Nice output for timestamp from mysql
 * @param $time String mysql timestamp
 * @return string Nicer output
 */
function drawTime($time)
{
    if(date("Ymd") == date("Ymd", strtotime($time))) {
        return strftime("Vandaag om %H:%M", strtotime($time));
    } else if (date("Ymd", strtotime("-1 days")) == date("Ymd", strtotime($time))) {
        return strftime("Gisteren om %H:%M", strtotime($time));
    } else {
        return strftime("%d/%m/%Y om %H:%M", strtotime($time));
    }
}

/**
 * Show the gemeente in a value
 */
function getGemeente()
{
    if(array_key_exists("gemeente", $_GET)) {
        return htmlspecialchars($_GET["gemeente"]);
    }
}

/**
 * Is user Logged In
 * @return bool
 */
function isLoggedIn()
{
    global $login;

    if( array_key_exists("username", $_GET) && array_key_exists("username", $_GET)) {
        return (isset($login[$_GET["username"]]) && $login[$_GET["username"]] == $_GET["password"]);
    }

    return false;
}

/**
 * GenerateURL
 * @return string URL
 */
function generateURL()
{
    $url = "index.php";

    if(isLoggedIn()) {
        $url .= "?username=" . $_GET["username"] . "&password=" . $_GET["password"];

        if (array_key_exists("gemeente", $_GET)) {
            $url .= "&gemeente=" . $_GET["gemeente"];
        }
    } else {
        if (array_key_exists("gemeente", $_GET)) {
            $url .= "?gemeente=" . $_GET["gemeente"];
        }
    }

    return $url;
}
?>
