<!--Autor: Sebastian Rieg
    Includes Einfügen -->
<?php
include 'includes/db.inc.php';
include 'includes/team.inc.php';
include 'includes/fahrer.inc.php';
//Sessions
$abgemeldet = false;
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    $abgemeldet = true;
}

// Eingabevalidierung und Teamregistrierung
$fehler = "";
$erfolg = "";

// Fügt Namen in Variablen ein damit sie in include dann per SQL Befehl ausgeführt werden können 
// Schaut ob es eine POST Anfrage ist && ob der Button "team_anlegen" gedrückt wurde, damit die Funktion teamEintragen() nur dann ausgeführt wird, wenn das Formular abgeschickt wird.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['team_anlegen'] === 'team') {
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

// Login Teamchef
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginname = trim($_POST['loginname']);
    $kennwort = $_POST['kennwort'];

    $stmt = $verbindung->prepare(
        "SELECT LoginName, KennwortTeamchef, Teamname, Vorname, Nachname 
         FROM Teamchef WHERE LoginName = ?"
    );
    $stmt->execute([$loginname]);
    $teamchef = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teamchef && password_verify($kennwort, $teamchef['KennwortTeamchef'])) {
        $_SESSION['teamchef_login'] = $teamchef['LoginName'];
        $_SESSION['teamchef_teamname'] = $teamchef['Teamname'];
        $_SESSION['teamchef_name'] = $teamchef['Vorname'] . ' ' . $teamchef['Nachname'];
        header('Location: teamchef/dashboard.php');
        exit;
    } else {
        $fehler = "Loginname oder Kennwort falsch.";
    }
}

// Login Rennveranstalter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $kennwort = $_POST['kennwort'];

    $stmt = $verbindung->prepare(
        "SELECT Name, Kennwort FROM Rennveranstalter WHERE Name = ?"
    );
    $stmt->execute([$name]);
    $veranstalter = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($veranstalter && password_verify($kennwort, $veranstalter['Kennwort'])) {
        $_SESSION['veranstalter_name'] = $veranstalter['Name'];
        header('Location: versanstalter/dashboard.php');
        exit;
    } else {
        $fehler = "Name oder Kennwort falsch.";
    }
}

// Registrierung Rennveranstalter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $kennwort = $_POST['kennwort'];

    if (empty($name) || empty($kennwort)) {
        $fehler = "Bitte alle Felder ausfüllen.";
    } else {
        // Prüfen ob Name bereits existiert
        $stmt = $verbindung->prepare("SELECT Name FROM Rennveranstalter WHERE Name = ?");
        $stmt->execute([$name]);

        if ($stmt->rowCount() > 0) {
            $fehler = "Ein Rennveranstalter mit diesem Namen existiert bereits.";
        } else {
            $kennwort_hash = password_hash($kennwort, PASSWORD_DEFAULT);
            $stmt = $verbindung->prepare(
                "INSERT INTO Rennveranstalter (Name, Kennwort) VALUES (?, ?)"
            );
            $stmt->execute([$name, $kennwort_hash]);
            $erfolg = "Registrierung erfolgreich! Sie können sich jetzt anmelden.";
        }
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
    <a href="index.php">Startseite Neu Laden</a>
    
    <h2>Anlegen eines Teams</h2>
    <?php if ($fehler): ?>
        <p style="color:red;"><?= htmlspecialchars($fehler) ?></p>
    <?php endif; ?>

    <?php if ($erfolg): ?>
        <p style="color:green;"><?= htmlspecialchars($erfolg) ?></p>
    <?php else: ?>
        <form action="index.php" method="post"> <!-- Wenn Registrierung erfolgreich, $erfolg ausgeben als Rückmeldung -->
            <label for="teamname">Teamname: 
            <input type="text" id="teamname" name="teamname" value="<?= isset($_POST['teamname']) ? htmlspecialchars($_POST['teamname']) : '' ?>" required></label>
            <br>
            <label for="nameteamchef">Name des Teamchefs: 
            <input type="text" id="nameteamchef" name="nameteamchef" value="<?= isset($_POST['nameteamchef']) ? htmlspecialchars($_POST['nameteamchef']) : '' ?>" required></label>
            <br>
            <label for="teamchef_login">Loginname:
            <input type="text" id="teamchef_login" name="teamchef_login" value="<?= isset($_POST['teamchef_login']) ? htmlspecialchars($_POST['teamchef_login']) : '' ?>" required></label>
            <br>   <!-- SQL §_Post nimmt das was eingegeben wurde und schreibt es in 'teamchef_login'-->
            <label for="teamchef_kennwort">Kennwort: 
            <input type="password" id="teamchef_kennwort" name="teamchef_kennwort" required></label>
            <br>
            <input type="submit" name="team_anlegen" value="Team anlegen">
        </form>
    <?php endif; ?>

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
        <?php if ($fehler): ?>
        <p style="color:red;"><?= htmlspecialchars($fehler) ?></p>
    <?php endif; ?>

    <?php if ($erfolg): ?>
        <p style="color:green;"><?= htmlspecialchars($erfolg) ?></p>
        <a href="veranstalter_login.php">Zum Login</a>
    <?php else: ?>
    <form method="post" action="Rennveranstalter.php">
        <label for="name">Name: 
        <input type="text" id="name" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required></label>
        <br>
        <label for="kennwort">Kennwort: <input type="password" id="kennwort" name="kennwort" required></label>
        <br>
        <input type="submit" value="Registrieren">
        </form>
    <?php endif; ?>