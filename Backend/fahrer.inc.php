<?php
// Autor: Sebastian Rieg

// Speichert oder ändert einen Fahrer; gibt die FahrerID zurück
// Wenn isNeu = true → INSERT, sonst → UPDATE
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

// Lädt alle Fahrer eines Teams
function fahrerLaden($verbindung, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer
         FROM Fahrer WHERE TeamName = ? ORDER BY FahrerName"
    );
    $stmt->execute([$teamName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lädt einen einzelnen Fahrer anhand FahrerID und TeamName
function fahrerLadenEinzeln($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer
         FROM Fahrer WHERE FahrerID = ? AND TeamName = ?"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Löscht einen Fahrer und seine Telefonnummern (Transaktion für Konsistenz)
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

// Lädt alle Telefonnummern eines Fahrers
function telefonnummernLaden($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT Telefonnummer FROM Telefonnummer
         WHERE FahrerID = ? AND TeamName = ? ORDER BY Telefonnummer"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Ersetzt alle Telefonnummern eines Fahrers (löschen + neu einfügen)
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

// Lädt alle verfügbaren Trainingsziele aus der Lookup-Tabelle
function trainingszieleAbrufen($verbindung)
{
    $stmt = $verbindung->prepare(
        "SELECT Trainingsziel FROM Trainingsziel ORDER BY Trainingsziel"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Speichert ein neues Training für einen Fahrer
// Pro Fahrer nur ein Training pro Tag (DB-Constraint über PK Datum+FahrerID)
function trainingErfassen($verbindung, $fahrerID, $teamName, $datum, $gefahreneKM, $trainingsziel)
{
    $stmt = $verbindung->prepare(
        "INSERT INTO Training (FahrerID, TeamName, Datum, gefahreneKM, Trainingsziel)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$fahrerID, $teamName, $datum, $gefahreneKM, $trainingsziel]);
}
