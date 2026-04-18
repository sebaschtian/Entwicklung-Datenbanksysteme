<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Startseite</title>
</head>
<body>
    <h1>Startseite</h1>

    <h2>1. Registrierung des Teamchefs</h2>
    <form method="post" action="Teamchef.html">
        <p><label>Vorname: <input type="text" name="vorname" required></label></p>
        <p><label>Nachname: <input type="text" name="nachname" required></label></p>
        <p><label>Email: <input type="email" name="email" required></label></p>
        <p><label>Passwort: <input type="password" name="passwort" required></label></p>
        <p><button type="submit">Registrieren</button></p>
    </form>

    <h2>2. Anmeldung Rennveranstalter</h2>
    <form method="post" action="Rennveranstalter.html">
        <p><label>Email: <input type="email" name="email" required></label></p>
        <p><label>Passwort: <input type="password" name="passwort" required></label></p>
        <p><button type="submit">Anmelden</button></p>
    </form>

    <h2>3. Anlegen eines Teams</h2>
    <form method="post" action="Teamchef.html">
        <p><label>Teamname: <input type="text" name="teamname" required></label></p>
        <p><label>Kurzbeschreibung: <input type="text" name="beschreibung"></label></p>
        <p><button type="submit">Team anlegen</button></p>
    </form>

</body>
</html>
