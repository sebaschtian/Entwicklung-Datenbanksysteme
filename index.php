//Autor: Sebastian Rieg
//Includes Einfügen
<?php
include 'includes/db.inc.php';
include 'includes/team.inc.php';
include 'includes/fahrer.inc.php';
//Sessions
session_start();

// Eingabevalidierung und Teamregistrierung
$fehler = "";
$erfolg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamname = trim($_POST['teamname']);
    $nameteamchef = trim($_POST['nameteamchef']);
    $loginname = trim($_POST['teamchef_login']);
    $kennwort = $_POST['teamchef_kennwort'];
    if (empty($teamname) || empty($nameteamchef) || empty($loginname) || empty($kennwort)) {
        $fehler = "Bitte alle Felder ausfüllen.";
    } elseif (teamExistiert($verbindung, $teamname)) {
        $fehler = "Ein Team mit diesem Namen existiert bereits.";
    } else {
        teamEintragen($verbindung, $teamname, $nameteamchef, $loginname, $kennwort);
        $erfolg = "Team wurde erfolgreich angelegt!";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Startseite</title>
</head>
<body>
    <h1>Startseite</h1>

    <h2>Anlegen eines Teams</h2>
    <form action="teamchef.php" method="post"> <!-- Wenn Registrierung erfolgreich, dann weiter zu Teamchef.php -->
        <label for="teamname">Teamname: 
        <input type="text" id="teamname" name="teamname" value="<?= isset($_POST['teamname']) ? htmlspecialchars($_POST['teamname']) : '' ?>" required></label>
        <br>
        <label for="nameteamchef">Name des Teamchefs: 
        <input type="text" id="nameteamchef" name="nameteamchef" value="<?= isset($_POST['nameteamchef']) ? htmlspecialchars($_POST['nameteamchef']) : '' ?>" required></label>
        <br>
        <label for="teamchef_login">Loginname:
        <input type="text" id="teamchef_login" name="teamchef_login" value="<?= isset($_POST['teamchef_login']) ? htmlspecialchars($_POST['teamchef_login']) : '' ?>" required></label>
            <!-- SQL §_Post erst auf Einzigartikeit prüfen, bei erfolg als neuen Wert speichern -->
        <label for="teamchef_kennwort">Kennwort: 
        <input type="password" id="teamchef_kennwort" name="teamchef_kennwort" required></label>
        <br>
        <input type="submit" value="Team anlegen">
    </form>

    <h2>Login Teamchef</h2>
    <form method="post" action="Teamchef.php">
        <label for="loginname">Loginname: <input type="text" id="loginname" name="loginname" required></label>
        <br>
        <label for="kennwort">Kennwort: <input type="password" id="kennwort" name="kennwort" required></label>
        <br>
        <input type="submit" value="Anmelden">
    </form>
        

    <h2>Anmeldung Rennveranstalter</h2>
    <form method="post" action="Rennveranstalter.php">
        <label for="name">Name: <input type="text" id="name" name="name" required></label>
        <br>
        <label for="kennwort">Kennwort: <input type="password" id="kennwort" name="kennwort" required></label>
        <br>
        <input type="submit" value="Anmelden">
    </form>

    <h2>Registrierung Rennveranstalter</h2>
    <form method="post" action="Rennveranstalter.php">
        <label for="name">Name: 
        <input type="text" id="name" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required></label>
        <br>
        <label for="kennwort">Kennwort: <input type="password" id="kennwort" name="kennwort" required></label>
        <br>
        <input type="submit" value="Registrieren">
    </form>

</body>
</htm