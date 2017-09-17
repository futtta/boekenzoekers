<?php
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
</head>
<body>

<form action="" method="get">
    Gemeente/Postcode:&nbsp;<input type="text" name="gemeente" size="100"><input type="submit" value="Zoek"><br/><br/>
</form>

<table border="1">
    <tr>
        <td>Postcode</td>
        <td>Gemeente</td>
        <td>Tekst</td>
        <td>Post Datum</td>
        <td>Facebook Link</td>
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
 * @param $search
 * @return array
 */
function getLimit($search = array())
{
    $limit = array("time" => "DESC");

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

    $data = $database->select("posts", "*", $search, $limit);

    foreach ($data as $row) {
        print("<tr>");
        print("<td>" . $row["zipcode"] . "</td>");
        print("<td>" . $row["gemeente"] . "</td>");
        print("<td>" . shortenText($row["text"]) . "</td>");
        print("<td>" . $row["time"] . "</td>");
        print("<td><a href=\"https://www.facebook.com/permalink.php?id=" . BOEKENJAGERS_GROUPID . "&v=wall&story_fbid=" . $row["postID"] . "\" target=\"_blank\">fb icon?</a></td>");
        print("</tr>");
    }
}

?>
