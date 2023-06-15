<?php
// Make the database connection ad leave it in the variable $pdo
require_once "pdo.php";
require_once "util.php";
session_start();

// If user is not logged in it redirect back to index.php with an error
if (!isset($_SESSION['user_id'])) {
    die("ACCESS DENIED");
}

// Handle the incoming data
if (isset($_SESSION['user_id']) && isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary'])) {

    $msg = validateProfile();
    if (is_string($msg)) {
        $_SESSION['error'] = $msg;
        header('Location: add.php');
        return;
    }

    // Validate position entries if present
    $msg = validatePos();
    if (is_string($msg)) {
        $_SESSION['error'] = $msg;
        header('Location: add.php');
        return;
    }

    // Ecode php array to JSON
    $stmt = $pdo->prepare('SELECT name FROM Institution
        WHERE name LIKE :prefix');
    $stmt->execute(array( ':prefix' => $_REQUEST['term']."%"));
    $school = array();
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        $school[] = $row['name'];
    }
    echo(json_encode($school, JSON_PRETTY_PRINT));

    // Data is valid - time to insert
    $stmt = $pdo->prepare('INSERT INTO Profile (user_id, first_name, last_name, email, headline, summary) VALUES ( :uid, :fn, :ln, :em, :he, :su)');
    $stmt->execute(array(
        ':uid' => $_SESSION['user_id'],
        ':fn' => $_POST['first_name'],
        ':ln' => $_POST['last_name'],
        ':em' => $_POST['email'],
        ':he' => $_POST['headline'],
        ':su' => $_POST['summary'])
      );
      $profile_id = $pdo->lastInsertId();

    // Insert the position entries
    $rank = 1;
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['year'.$i]) ) continue;
        if ( ! isset($_POST['desc'.$i]) ) continue;
        $year = $_POST['year'.$i];
        $desc = $_POST['desc'.$i];

        $stmt = $pdo->prepare('INSERT INTO Position (profile_id, rank, year, description) VALUES ( :pid, :rank, :year, :desc)');
        $stmt->execute(array(
            ':pid' => $profile_id,
            ':rank' => $rank,
            ':year' => $year,
            ':desc' => $desc));
        $rank++;
    }

    insertEducations($pdo, $_REQUEST['profile_id']);

    $_SESSION['success'] = 'Record Added';
    header( 'Location: index.php' ) ;
    return;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Olga Rogozhnikova</title>
    <?php require_once "head.php"; ?>
</head>
<body>
<p>Add A New Profile</p>
<?php flashMessages(); ?>
<form method="post">
    <p>First name:
        <input type="text" name="first_name">
    </p>
    <p>Last name:
        <input type="text" name="last_name">
    </p>
    <p>Email:
        <input type="text" name="email">
    </p>
    <p>Headline:
        <input type="text" name="headline">
    </p>
    <p>Summary:
        <textarea rows="8" name="summary" cols="80"></textarea>
    </p>
    <p>Education:
        <input type="submit" id="addEdu" value="+">
    </p>
    <div id="edu_fields"></div>

    <p>Position:
        <input type="submit" id="addPos" value="+">
    </p>
    <div id="position_fields"></div>

<p><input type="submit" value="Add New"/>
<a href="index.php">Cancel</a></p>
</form>
</body>
</html>

<script>
    countPos = 0;
    countEdu = 0;

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
        });

        $('.school').autocomplete({
            function () {
                $.getJSON('school.php', function(data) {
                    $('.school').html(data);
                })
            }
        });

    });
</script>