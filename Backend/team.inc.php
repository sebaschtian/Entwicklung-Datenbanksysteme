<?php
// Autor: Sebastian Rieg
// Prüft ob ein Team bereits existiert
function teamExistiert($verbindung, $teamname)
{
    $stmt = $verbindung->prepare("SELECT TeamName FROM Team WHERE TeamName = ?");
    $stmt->execute([$teamname]);
    return $stmt->rowCount() > 0;
}

// Trägt neues Team und Teamchef in die Datenbank ein
function teamEintragen($verbindung, $teamname, $nameteamchef, $loginname, $kennwort)
{
    $kennwort_hash = password_hash($kennwort, PASSWORD_DEFAULT);
    $stmt = $verbindung->prepare(
        "INSERT INTO Teamchef (NameTeamchef, LoginName, Kennwort, TeamName) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$nameteamchef, $loginname, $kennwort_hash, $teamname]);
}