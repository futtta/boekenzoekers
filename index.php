<?php
date_default_timezone_set("Europe/Brussels");

//Boekenjagers FB GROUP ID
define("BOEKENJAGERS_GROUPID", 173371763090905);


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
        <label for="gemeente">Gemeente/Postcode:</label>&nbsp;
        <input type="text" id="gemeente" name="gemeente" placeholder="Postcode of Gemeentenaam" value="<?php getGemeente(); ?>">&nbsp;
        <input type="submit" value="Zoek">
    </p>
</form>

<table>
    <tr>
        <th>Postcode</th>
        <th>Gemeente</th>
        <th>Tekst</th>
        <th>Post Datum</th>
    </tr>
    <?php drawPosts(); ?>
</table>

</body>
</html>

<?php
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
    global $database;

    $search = getSearch();
    $limit = getLimit($search);
    $count = $database->count("posts", "*", $search, $limit);

    if($count == 0) {
        print("<tr>");
        print("<td colspan=\"4\">Geen resultaten gevonden: <b>Keep calm and hide a book!</b></td>");
        print("</tr>");
    } else {
        $data = $database->select("posts", "*", $search, $limit);

        foreach ($data as $row) {
            print("<tr>");
            print("<td>" . $row["zipcode"] . "</td>");
            print("<td>" . $row["gemeente"] . "</td>");
            print("<td><a href=\"https://www.facebook.com/permalink.php?id=" . BOEKENJAGERS_GROUPID . "&v=wall&story_fbid=" . $row["postID"] . "\" target=\"_blank\">" . shortenText($row["text"]) . "</a></td>");
            print("<td>" . drawTime($row["time"]) . "</td>");
            print("</tr>");
        }
    }
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
    if(isset($_GET["gemeente"])) {
        print(htmlspecialchars($_GET["gemeente"]));
    }
}
?>
