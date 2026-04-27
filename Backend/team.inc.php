<?php
// Autor: Sebastian Rieg

// Prüft ob ein Team bereits existiert
function teamExistiert($verbindung, $teamName)
{
    $stmt = $verbindung->prepare("SELECT TeamName FROM Team WHERE TeamName = ?");
    $stmt->execute([$teamName]);
    return $stmt->rowCount() > 0;
}

// Trägt neues Team und Teamchef in die Datenbank ein
function teamEintragen($verbindung, $teamName, $nameTeamchef, $loginName, $kennwort)
{
    $kennwortHash = password_hash($kennwort, PASSWORD_DEFAULT);

    $verbindung->beginTransaction();
    try {
        // Insert in Team
        $stmt = $verbindung->prepare("INSERT INTO Team (TeamName) VALUES (?)");
        $stmt->execute([$teamName]);

        // Insert in Teamchef
        $stmt = $verbindung->prepare(
            "INSERT INTO Teamchef (NameTeamchef, LoginName, Kennwort, TeamName) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nameTeamchef, $loginName, $kennwortHash, $teamName]);

        $verbindung->commit();
    } catch (Exception $e) {
        $verbindung->rollBack();
        throw $e;
    }
}
