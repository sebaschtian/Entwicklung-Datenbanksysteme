<?php
// Auor: Sebastian Rieg
// Returns true if a team with this name already exists.
function teamExistiert($verbindung, $teamName)
{
    $stmt = $verbindung->prepare("SELECT TeamName FROM Team WHERE TeamName = ?");
    $stmt->execute([$teamName]);
    return $stmt->rowCount() > 0;
}

// Registers a new team and its coach in a single transaction.
function teamEintragen($verbindung, $teamName, $nameTeamchef, $loginName, $kennwort)
{
    $kennwortHash = password_hash($kennwort, PASSWORD_DEFAULT);

    $verbindung->beginTransaction();
    try {
        $stmt = $verbindung->prepare("INSERT INTO Team (TeamName) VALUES (?)");
        $stmt->execute([$teamName]);

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

// Verifies login; returns the coach row (including TeamName) on success, or false.
function teamchefLogin($verbindung, $loginName, $kennwort)
{
    $stmt = $verbindung->prepare(
        "SELECT LoginName, Kennwort, TeamName
         FROM Teamchef WHERE LoginName = ?"
    );
    $stmt->execute([$loginName]);
    $teamchef = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teamchef && password_verify($kennwort, $teamchef['Kennwort'])) {
        return $teamchef;
    }
    return false;
}
