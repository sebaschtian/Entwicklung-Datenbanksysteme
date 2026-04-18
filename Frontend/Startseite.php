<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Startseite</title>
</head>
<body>
    <h1>Startseite</h1>

    <h2>1. Registrierung des Teamchefs</h2>
    <form action="teamchef.php" method="post"> //Wenn Registreirung erfolgreich, dann weiter zu Teamchef.php
            <label for="teamname">Teamname: 
            <input type="text" id="teamname" name="teamname" value="<?= isset($_POST['teamname']) ? htmlspecialchars($_POST['teamname']) : '' ?>" required></label>

            <label for="teamchef_vorname">Vorname: 
            <input type="text" id="teamchef_vorname" name="teamchef_vorname" value="<?= isset($_POST['teamchef_vorname']) ? htmlspecialchars($_POST['teamchef_vorname']) : '' ?>" required></label>

            <label for="teamchef_nachname">Nachname:
            <input type="text" id="teamchef_nachname" name="teamchef_nachname" value="<?= isset($_POST['teamchef_nachname']) ? htmlspecialchars($_POST['teamchef_nachname']) : '' ?>" required></label>

            <label for="teamchef_login">Loginname:
            <input type="text" id="teamchef_login" name="teamchef_login" value="<?= isset($_POST['teamchef_login']) ? htmlspecialchars($_POST['teamchef_login']) : '' ?>" required></label>

        <label for="teamchef_kennwort">Kennwort: 
        <input type="password" id="teamchef_kennwort" name="teamchef_kennwort" required></label>
            
        <input type="submit" value="Team anlegen">
    </form>

    <h2>2. Anmeldung Rennveranstalter</h2>
    <form method="post" action="Rennveranstalter.php">
        <p><label>Email: <input type="email" name="email" required></label></p>
        <p><label>Passwort: <input type="password" name="passwort" required></label></p>
        <p><button type="submit">Anmelden</button></p>
    </form>

    <h2>3. Anlegen eines Teams</h2>
    <form method="post" action="Teamchef.php">
        <p><label>Teamname: <input type="text" name="teamname" required></label></p>
        <p><label>Kurzbeschreibung: <input type="text" name="beschreibung"></label></p>
        <p><button type="submit">Team anlegen</button></p>
    </form>

</body>
</html>
