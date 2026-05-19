<?php
// Autor: Sebastian Rieg

// Speichert oder ändert einen Fahrer; gibt die FahrerID zurück
// Wenn isNeu = true → INSERT, sonst → UPDATE
function fahrerSpeichern($verbindung, $fahrerID, $teamName, $fahrerName, $ortName, $plz, $strasseHausnummer, $isNeu)
{
    if ($isNeu) {
        $ergebnis   = $verbindung->query("SELECT COALESCE(MAX(FahrerID), 0) + 1 FROM Fahrer");
        $naechsteID = max(1, (int) $ergebnis->fetchColumn(0));
        $stmt = $verbindung->prepare(
            "INSERT INTO Fahrer (FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer, TeamName)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$naechsteID, $fahrerName, $ortName, $plz, $strasseHausnummer, $teamName]);
        return $naechsteID;
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

// Löscht einen Fahrer und seine Telefonnummern via Stored Procedure
function fahrerLoeschen($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare("CALL FahrerLoeschen(?, ?)");
    $stmt->execute([$fahrerID, $teamName]);
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

// Ersetzt alle Telefonnummern eines Fahrers (löschen + neu einfügen, atomar)
function telefonnummernSpeichern($verbindung, $fahrerID, $teamName, array $nummern)
{
    $verbindung->beginTransaction();
    try {
        $stmtDel = $verbindung->prepare(
            "DELETE FROM Telefonnummer WHERE FahrerID = ? AND TeamName = ?"
        );
        $stmtDel->execute([$fahrerID, $teamName]);

        $stmtIns = $verbindung->prepare(
            "INSERT INTO Telefonnummer (FahrerID, TeamName, Telefonnummer) VALUES (?, ?, ?)"
        );
        foreach ($nummern as $nr) {
            $nr = trim($nr);
            if ($nr !== '') {
                $stmtIns->execute([$fahrerID, $teamName, $nr]);
            }
        }
        $verbindung->commit();
    } catch (Exception $e) {
        $verbindung->rollBack();
        throw $e;
    }
}

// Lädt alle verfügbaren Trainingsziele aus der Lookup-Tabelle
function trainingszieleAbrufen($verbindung)
{
    return $verbindung->query(
        "SELECT Trainingsziel FROM Trainingsziel ORDER BY Trainingsziel"
    )->fetchAll(PDO::FETCH_COLUMN);
}

// Fügt ein neues Trainingsziel hinzu
function trainingsZielHinzufuegen($verbindung, $trainingsziel)
{
    $stmt = $verbindung->prepare(
        "INSERT INTO Trainingsziel (Trainingsziel) VALUES (?)"
    );
    $stmt->execute([$trainingsziel]);
}

// Löscht ein Trainingsziel
function trainingsZielLoeschen($verbindung, $trainingsziel)
{
    $stmt = $verbindung->prepare(
        "DELETE FROM Trainingsziel WHERE Trainingsziel = ?"
    );
    $stmt->execute([$trainingsziel]);
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

// Lädt alle angemeldeten Fahrer des Teams je zukünftigem Rennen; gibt Map RennID → [FahrerName] zurück
function angemeldteFahrerProRennen($verbindung, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT n.RennID, f.FahrerName
         FROM nimmtTeil n
         JOIN Fahrer f ON n.FahrerID = f.FahrerID AND n.TeamName = f.TeamName
         JOIN Rennen r ON n.RennID = r.RennID
         WHERE n.TeamName = ? AND r.Datum > CURDATE()
         ORDER BY n.RennID, f.FahrerName"
    );
    $stmt->execute([$teamName]);
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $zeile) {
        $map[$zeile['RennID']][] = $zeile['FahrerName'];
    }
    return $map;
}

