<?php
// Autor: Sebastian Rieg
session_start();
 
require 'Backend/db.inc.php';
require 'Backend/team.inc.php';
require 'Backend/fahrer.inc.php';
require 'Backend/veranstalter.inc.php';
 
// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
}
 
$fehlerTeam         = "";
$fehlerTeamchef     = "";
$fehlerVeranstalter = "";
$erfolg             = "";
 
// ── Team anlegen ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_anlegen'])) {
    $teamName     = trim($_POST['teamname']);
    $nameTeamchef = trim($_POST['nameteamchef']);
    $loginName    = trim($_POST['teamchef_loginname']);
    $kennwort     = $_POST['teamchef_kennwort'];
 
    if (empty($teamName) || empty($nameTeamchef) || empty($loginName) || empty($kennwort)) {
        $fehlerTeam = "Bitte alle Felder ausfüllen.";
    } elseif (teamExistiert($verbindung, $teamName)) {
        $fehlerTeam = "Ein Team mit diesem Namen existiert bereits.";
    } else {
        teamEintragen($verbindung, $teamName, $nameTeamchef, $loginName, $kennwort);
        $erfolg = "team_anlegen";
    }
}
 
// ── Login Teamchef ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teamchef_anmelden'])) {
    $loginName = trim($_POST['loginname']);
    $kennwort  = $_POST['kennwort'];
 
    $stmt = $verbindung->prepare(
        "SELECT LoginName, Kennwort, TeamName, NameTeamchef 
         FROM Teamchef WHERE LoginName = ?"
    );
    $stmt->execute([$loginName]);
    $teamchef = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($teamchef && password_verify($kennwort, $teamchef['Kennwort'])) {
        $_SESSION['teamchef_login']    = $teamchef['LoginName'];
        $_SESSION['teamchef_teamname'] = $teamchef['TeamName'];
        $_SESSION['teamchef_name']     = $teamchef['NameTeamchef'];
        header('Location: Fahrerverwaltung.php');
        exit;
    } else {
        $fehlerTeamchef = "Loginname oder Kennwort falsch.";
    }
}
 
// ── Login Rennveranstalter ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['veranstalter_anmelden'])) {
    $veranstalterName = trim($_POST['veranstalter_name']);
    $kennwort         = $_POST['veranstalter_kennwort'];
 
    if (veranstalterLogin($verbindung, $veranstalterName, $kennwort)) {
        $_SESSION['veranstalter_name'] = $veranstalterName;
        header('Location: Rennveranstalter.php');
        exit;
    } else {
        $fehlerVeranstalter = "Name oder Kennwort falsch.";
    }
}
 
// ── Registrierung Rennveranstalter ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['veranstalter_registrieren'])) {
    $veranstalterName = trim($_POST['veranstalter_name']);
    $kennwort         = $_POST['veranstalter_kennwort'];
 
    if (empty($veranstalterName) || empty($kennwort)) {
        $fehlerVeranstalter = "Bitte alle Felder ausfüllen.";
    } elseif (veranstalterExistiert($verbindung, $veranstalterName)) {
        $fehlerVeranstalter = "Ein Veranstalter mit diesem Namen existiert bereits.";
    } else {
        veranstalterRegistrieren($verbindung, $veranstalterName, $kennwort);
        $erfolg = "veranstalter_registrieren";
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
    <a href="index.php">Startseite neu laden</a>
 
    <?php if (isset($_GET['logout'])): ?>
        <p style="color:green;">Sie wurden erfolgreich abgemeldet.</p>
    <?php endif; ?>
 
    <!-- ── Team anlegen ── -->
    <h2>Anlegen eines Teams</h2>
    <?php if ($fehlerTeam): ?>
        <p style="color:red;"><?= htmlspecialchars($fehlerTeam) ?></p>
    <?php endif; ?>
    <?php if ($erfolg === 'team_anlegen'): ?>
        <p style="color:green;">Team wurde erfolgreich angelegt!</p>
    <?php else: ?>
    <form action="index.php" method="post">
        <label>Teamname:
            <input type="text" name="teamname"
                value="<?= isset($_POST['teamname']) ? htmlspecialchars($_POST['teamname']) : '' ?>" required>
        </label><br>
        <label>Name des Teamchefs:
            <input type="text" name="nameteamchef"
                value="<?= isset($_POST['nameteamchef']) ? htmlspecialchars($_POST['nameteamchef']) : '' ?>" required>
        </label><br>
        <label>Loginname:
            <input type="text" name="teamchef_loginname"
                value="<?= isset($_POST['teamchef_loginname']) ? htmlspecialchars($_POST['teamchef_loginname']) : '' ?>" required>
        </label><br>
        <label>Kennwort:
            <input type="password" name="teamchef_kennwort" required>
        </label><br>
        <input type="submit" name="team_anlegen" value="Team anlegen">
    </form>
    <?php endif; ?>
 
    <!-- ── Login Teamchef ── -->
    <h2>Login Teamchef</h2>
    <?php if ($fehlerTeamchef): ?>
        <p style="color:red;"><?= htmlspecialchars($fehlerTeamchef) ?></p>
    <?php endif; ?>
    <form action="index.php" method="post">
        <label>Loginname:
            <input type="text" name="loginname" required>
        </label><br>
        <label>Kennwort:
            <input type="password" name="kennwort" required>
        </label><br>
        <input type="submit" name="teamchef_anmelden" value="Anmelden">
    </form>
 
    <!-- ── Rennveranstalter Anmeldung & Registrierung ── -->
    <h2>Anmeldung / Registrierung Rennveranstalter</h2>
    <?php if ($fehlerVeranstalter): ?>
        <p style="color:red;"><?= htmlspecialchars($fehlerVeranstalter) ?></p>
    <?php endif; ?>
    <?php if ($erfolg === 'veranstalter_registrieren'): ?>
        <p style="color:green;">Registrierung erfolgreich! Sie können sich jetzt anmelden.</p>
    <?php endif; ?>
    <form action="index.php" method="post">
        <label>Name:
            <input type="text" name="veranstalter_name"
                value="<?= isset($_POST['veranstalter_name']) ? htmlspecialchars($_POST['veranstalter_name']) : '' ?>" required>
        </label><br>
        <label>Kennwort:
            <input type="password" name="veranstalter_kennwort" required>
        </label><br>
        <input type="submit" name="veranstalter_anmelden" value="Anmelden">
        <input type="submit" name="veranstalter_registrieren" value="Registrieren">
    </form>
 
</body>
</html>