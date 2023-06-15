<?php
// Make the database connection ad leave it in the variable $pdo
require_once "pdo.php";
require_once "util.php";
session_start();
require_once "head.php";

?>

<title>Olga Rogozhnikova</title>
<h1>Welcome to the Profile Database</h1>

<?php
   flashMessages();

    if (isset($_SESSION['user_id'])) {
        echo('<table border="1">'."\n");
        $stmt = $pdo->query("SELECT profile_id, user_id, first_name, last_name, email, headline, summary FROM Profile");
        while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            echo "<tr><td>";
            echo(htmlentities(($row['first_name']) . " " . ($row['last_name'])));
            echo("</td><td>");
            echo(htmlentities($row['headline']));
            echo("</td><td>");
            echo('<a href="edit.php?profile_id='.htmlentities($row['profile_id']).'">Edit</a> / ');
            echo('<a href="delete.php?profile_id='.htmlentities($row['profile_id']).'">Delete</a>');
            echo("</td></tr>\n");
            }
            echo ("</table>");
            echo ('<a href="add.php">Add New Entry</a>');
            echo('<div><a href="logout.php">Logout</a></div>');
        }

    if (!isset($_SESSION['user_id'])) {
        echo('<table border="1">'."\n");
        $stmt = $pdo->query("SELECT profile_id user_id, first_name, last_name, email, headline, summary FROM Profile");
        while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            echo "<tr><td>";
            echo(htmlentities(($row['first_name']) . " " . ($row['last_name'])));
            echo("</td><td>");
            echo(htmlentities($row['headline']));
            echo("</td></tr>\n");
        }
        echo ("</table>");
        echo('<a href="login.php">Please log in</a>');

    }
?>