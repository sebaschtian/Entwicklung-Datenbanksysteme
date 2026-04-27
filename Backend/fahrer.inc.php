<?php
// Autor: Sebastian Rieg

// Speichert oder ändert einen Fahrer; gibt die FahrerID zurück
function fahrerSpeichern($verbindung, $fahrerID, $teamName, $fahrerName, $ortName, $plz, $strasseHausnummer, $isNeu)
{
    if ($isNeu) {
        $stmt = $verbindung->prepare(
            "INSERT INTO Fahrer (FahrerName, OrtName, PLZ, StrasseHausnummer, TeamName)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$fahrerName, $ortName, $plz, $strasseHausnummer, $teamName]);
        return (int) $verbindung->lastInsertId();
    } else {
        $stmt = $verbindung->prepare(
            "UPDATE Fahrer SET FahrerName = ?, OrtName = ?, PLZ = ?, StrasseHausnummer = ?
             WHERE FahrerID = ? AND TeamName = ?"
        );
        $stmt->execute([$fahrerName, $ortName, $plz, $strasseHausnummer, $fahrerID, $teamName]);
        return $fahrerID;
    }
}

function fahrerLaden($verbindung, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer
         FROM Fahrer WHERE TeamName = ? ORDER BY FahrerName"
    );
    $stmt->execute([$teamName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fahrerLadenEinzeln($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer
         FROM Fahrer WHERE FahrerID = ? AND TeamName = ?"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fahrerLoeschen($verbindung, $fahrerID, $teamName)
{
    $verbindung->beginTransaction();
    try {
        $stmt = $verbindung->prepare(
            "DELETE FROM Telefonnummer WHERE FahrerID = ? AND TeamName = ?"
        );
        $stmt->execute([$fahrerID, $teamName]);

        $stmt = $verbindung->prepare(
            "DELETE FROM Fahrer WHERE FahrerID = ? AND TeamName = ?"
        );
        $stmt->execute([$fahrerID, $teamName]);

        $verbindung->commit();
    } catch (Exception $e) {
        $verbindung->rollBack();
        throw $e;
    }
}

function telefonnummernLaden($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT Telefonnummer FROM Telefonnummer
         WHERE FahrerID = ? AND TeamName = ? ORDER BY Telefonnummer"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function telefonnummernSpeichern($verbindung, $fahrerID, $teamName, array $nummern)
{
    $stmt = $verbindung->prepare(
        "DELETE FROM Telefonnummer WHERE FahrerID = ? AND TeamName = ?"
    );
    $stmt->execute([$fahrerID, $teamName]);

    $stmt = $verbindung->prepare(
        "INSERT INTO Telefonnummer (FahrerID, TeamName, Telefonnummer) VALUES (?, ?, ?)"
    );
    foreach ($nummern as $nr) {
        $nr = trim($nr);
        if ($nr !== '') {
            $stmt->execute([$fahrerID, $teamName, $nr]);
        }
    }
}