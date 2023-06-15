<?php
// Make the database connection ad leave it in the variable $pdo
require "pdo.php";
require "util.php";
session_start();

// If user is not logged in it redirect back to index.php with an error
if (!isset($_SESSION['user_id'])) {
    die("ACCESS DENIED");
}

// Make sure the REQUEST parameter is present
if ( ! isset($_REQUEST['profile_id']) ) {
    $_SESSION['error'] = "Missing profile_id";
    header('Location: index.php');
    return;
  }

// Load up the profile in question
$stmt = $pdo->prepare("SELECT * FROM Profile where profile_id = :xyz AND user_id = :uid");
$stmt->execute(array(
    ":xyz" => $_REQUEST['profile_id'],
    ":uid" => $_SESSION['user_id']
));
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $profile === false ) {
    $_SESSION['error'] = 'Could not load profile';
    header( 'Location: index.php' ) ;
    return;
}

// Handle icoming data
if (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary'])) {

    // Data validation
    $msg = validateProfile();
    if (is_string($msg)) {
        $_SESSION['error'] = $msg;
        header("Location: edit.php?profile_id=" . $_REQUEST['profile_id']);
        return;
    }

    // Validate position entries if present
    $msg = validatePos();
    if (is_string($msg)) {
        $_SESSION['error'] = $msg;
        header("Location: edit.php?profile_id=" . $_REQUEST['profile_id']);
        return;
    }

    $sql = "UPDATE Profile SET first_name = :fn,
            last_name = :ln, email = :em, headline = :he, summary = :su
            WHERE profile_id = :pid AND user_id = :uid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        ':pid' => $_REQUEST['profile_id'],
        ':uid' => $_SESSION['user_id'],
        ':fn' => $_POST['first_name'],
        ':ln' => $_POST['last_name'],
        ':em' => $_POST['email'],
        ':he' => $_POST['headline'],
        ':su' => $_POST['summary']
    ));
    $profile_id = $pdo->lastInsertId();

    // Clear out the old position entries
    $stmt = $pdo->prepare('DELETE FROM Position
        WHERE profile_id=:pid');
    $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));

    insertPositions($pdo, $_REQUEST['profile_id']);

    // Clear out the old education entry
    $stmt = $pdo->prepare('DELETE FROM Education
        WHERE profile_id=:pid');
    $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));

    insertEducations($pdo, $_REQUEST['profile_id']);

    $_SESSION['success'] = 'Profile updated';
    header( 'Location: index.php' ) ;
    return;
}

// Load up the position rows
$stmt = $pdo->prepare("SELECT * FROM Position
    WHERE profile_id = :xyz");
$stmt->execute(array(":xyz" => $_GET['profile_id']));
$positions = $stmt->fetchAll();

// Load up the education rows
$stmt = $pdo->prepare("SELECT year, name FROM Education
    JOIN Institution
        ON Education.institution_id = Institution.institution_id
    WHERE profile_id = :xyz");
$stmt->execute(array(":xyz" => $_GET['profile_id']));
$educations = $stmt->fetchAll();
// Load up the position rows
// $positions = loadPos($pdo, $_REQUEST['profile_id']);

// Flash pattern
flashMessages();

$first_name = htmlentities($profile['first_name']);
$last_name = htmlentities($profile['last_name']);
$email = htmlentities($profile['email']);
$headline = htmlentities($profile['headline']);
$summary = htmlentities($profile['summary']);
$profile_id = $_REQUEST['profile_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Olga Rogozhnikova</title>
    <?php require_once "head.php"; ?>
</head>
<body>
    <p>Edit Profile</p>
    <form method="post">
        <p>First Name:
            <input type="text" name="first_name" value="<?= $first_name ?>">
        </p>
        <p>Last Name:
            <input type="text" name="last_name" value="<?= $last_name ?>">
        </p>
        <p>Email:
            <input type="text" name="email" value="<?= $email ?>">
        </p>
        <p>Headline:
            <input type="text" name="headline" value="<?= $headline ?>">
        </p>
        <p>Summary:
            <input type="text" name="summary" value="<?= $summary ?>">
        </p>
            <input type="hidden" name="profile_id" value="<?= $profile_id ?>">

    <?php
        $edu = 0;
        echo ('<p>Education: <input type="submit" id="addEdu" value="+">'."\n");
        echo ('<div id="edu_fields">'."\n");
        if (count($schools) > 0) {
            foreach($schools as $school) {
                $edu++;
                echo ('<div id="edu'.$edu.'">'."\n");
                echo ('<p>Year: <input type="text" name="edu_year'.$edu.'"');
                echo (' value="'.$school['year'].'" />'."\n");
                echo ('<input type="button" value="-" ');
                echo ('onclick="$(\'#position'.$edu.'\').remove();return false;">'."\n");
                echo ("</p>\n");
                echo ('<textarea name="edu_school'.$edu.'" rows="8" cols="80">'."\n");
                echo (htmlentities($school['name'])."\n");
                echo ("\n</textarea>\n</div>\n");
            }
        }
        echo ("</div></p>\n");

        $pos = 0;
        echo ('<p>Position: <input type="submit" id="addPos" value="+">'."\n");
        echo ('<div id="position_fields">'."\n");
        foreach($positions as $position) {
            $pos++;
            echo ('<div id="position'.$pos.'">'."\n");
            echo ('<p>Year: <input type="text" name="year'.$pos.'"');
            echo (' value="'.$position['year'].'" />'."\n");
            echo ('<input type="button" value="-" ');
            echo ('onclick="$(\'#position'.$pos.'\').remove();return false;">'."\n");
            echo ("</p>\n");
            echo ('<textarea name="desc'.$pos.'" rows="8" cols="80">'."\n");
            echo (htmlentities($position['description'])."\n");
            echo ("\n</textarea>\n</div>\n");
        }
        echo ("</div></p>\n");
        ?>

        <p>
            <input type="submit" value="Save"/>
            <a href="index.php">Cancel</a>
        </p>
    </form>
</body>
</html>

<script>
    countPos = <?= $pos ?>;
    countEdu = <?= $edu ?>;


    $('document').ready(function(){
        window.console && console.log('Document ready called');
        $('#addPos').click(function(event) {
            event.preventDefault();
            if (countPos >=9) {
                alert('Maximum of nine position entries exceeded');
                return;
            }
            countPos++;
            window.console && console.log("Adding position" + countPos);
            $('#position_fields').append(
                '<div id="position'+countPos+'"> \
                    <p>Year: <input type="text" name="year'+countPos+'" value=""> \
                    <input type="button" value="-" \
                        onclick="$(\'#position'+countPos+'\').remove();return false;"></p> \
                    <textarea name="desc'+countPos+'" rows="8" cols="80"></textarea> \
                </div>');
        });

        $('#addEdu').click(function(event) {
            event.preventDefault();
            if (countEdu >=9) {
                alert('Maximum of nine education entries exceeded');
                return;
            }
            countEdu++;
            window.console && console.log("Adding education" + countEdu);

            $('#edu_fields').append(
                '<div id="edu'+countEdu+'"> \
                    <p>Year: <input type="text" name="edu_year'+countEdu+'" value="" /> \
                    <input type="button" value="-" \
                        onclick="$(\'#edu'+countEdu+'\').remove();return false;"></p> \
                    <p>School: <input type="text" size="80" name="edu_school'+countEdu+'" class="school" value="" /> \
                </p></div>');

            $('.school').autocomplete({
                source: "school.php"
            });
        });

        $('.school').autocomplete({
            source: "school.php"
        });

    });
</script>