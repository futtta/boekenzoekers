<?php
date_default_timezone_set("Europe/Brussels");

//Includes
require_once("inc/config.php");
require_once("vendor/autoload.php");

// set CORS header
if (isset($corsDomains)) {
    header("Access-Control-Allow-Origin: ".$corsDomains);
}

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


if (array_key_exists("output",$_GET) && $_GET["output"]==="rss") {
    outputFeed();
} else if (array_key_exists("output",$_GET) && $_GET["output"]==="json") {
    outputJSON();
} else {
    outputHTML();
}

function outputJSON() {
    $data=getPostsFromDB();
    foreach ($data as $row) {
        $thisRow["gemeente"]=$row["gemeente"];
        $thisRow["zipcode"]=$row["zipcode"];
        $thisRow["text"]=htmlentities($row["text"]);
        $thisRow["fbURL"]=buildFBurl($row["postID"]);
        if (!empty($row["auteur"])) {
            $thisRow["auteur"]=htmlentities($row["auteur"]);
        }
        $dataOut[]=$thisRow;
    }

    echo json_encode($dataOut);
}

function outputFeed() {
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
                ->addItemLink(buildFBurl($row["postID"]))
                ->addItemPubDate($row["time"])
                ->addItemDescription(htmlentities($row["text"]));
            if (!empty($row["auteur"])) {
                $feed->addItemAuthor($row["auteur"]);
            }
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

    <form id="boekzoekform" action="">
        <p>
            <label for="gemeente" class="desktop">Gemeente/Postcode:</label>&nbsp;
            <input type="text" id="gemeente" name="gemeente" placeholder="Postcode of Gemeentenaam" value="<?php echo getGemeente(); ?>">&nbsp;
            <input id="boekzoekknop" type="submit" value="Zoek">
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

        // zipcode of gemeente/auteur
        if (!is_numeric($zipcode)) {
            // variaties van sint/st/st.
            if (preg_match("/(st\.?|sint)[-|\s]([A-z]*)/i", $zipcode, $matches)) {
                $zipcode = "sint-".$matches[2];
            }
            $_search["OR"]["gemeente[~]"] = $zipcode;
            $_search["OR"]["auteur[~]"] = $zipcode;
        } else {
            $_search["zipcode"] = $zipcode;
        }

        // als we een timeFrom met UNIX timestamp hebben (of iets dat er op lijkt) voegen we die toe aan de query
        if ( isset($_GET["timeFrom"]) && is_numeric($_GET["timeFrom"]) && strlen($_GET["timeFrom"]) === 10 ) {
            $timeFrom = date("Y-m-d H:i:s",$_GET["timeFrom"]);
            $search["AND"]=$_search;
            $search["AND"]["time[>]"] = $timeFrom;
        } else {
            $search=$_search;
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
            print("<td><a href=\"".buildFBurl($row["postID"])."\" target=\"_blank\">" . shortenText($row["text"]) . "</a>");
            if (!empty($row["auteur"])) {
                print (" <i>(door " . $row["auteur"] . ")</i>");
            }

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

/**
 * buildFBurl
 * @param $postID string with FB post ID
 * @return string full FB story URL
 */
function buildFBurl($postID) {
    global $fbGroupID;
    return "https://www.facebook.com/permalink.php?id=" . $fbGroupID . "&v=wall&story_fbid=" . $postID;
}
?>
