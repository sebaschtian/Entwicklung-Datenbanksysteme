<?php
// Autor: Sebastian Rieg
// Prüft ob ein Team bereits existiert
function teamExistiert($verbindung, $teamname)
{
    $stmt = $verbindung->prepare("SELECT TeamName FROM Teamchef WHERE TeamName = ?");
    $stmt->execute([$teamname]);
    return $stmt->rowCount() > 0;
}

// Trägt neues Team und Teamchef in die Datenbank ein
function teamEintragen($verbindung, $teamname, $nameteamchef, $loginname, $kennwort)
{
    $kennwort_hash = password_hash($kennwort, PASSWORD_DEFAULT);

    //Inserts in Team
    $stmt = $verbindung->prepare(
        "INSERT INTO Team (TeamName) VALUES (?)"
    );
    
    //Inserts in Teamchef
    $stmt = $verbindung->prepare(
        "INSERT INTO Teamchef (NameTeamchef, LoginName, Kennwort, TeamName) 
         VALUES (?, ?, ?, ?)"
    );
    if (!$stmt->execute([$nameteamchef, $loginname, $kennwort_hash, $teamname])) {
        die("Fehler beim Eintragen: " . implode(" ", $stmt->errorInfo()));
    }
}

