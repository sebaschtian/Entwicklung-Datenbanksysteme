<?php
// Autor: Sebastian Rieg

// Speichert oder ändert einen Fahrer; gibt die FahrerID zurück
// Wenn isNeu = true → INSERT, sonst → UPDATE
function fahrerSpeichern($verbindung, $fahrerID, $teamName, $fahrerName, $ortName, $plz, $strasseHausnummer, $isNeu)
{
    if ($isNeu) {
        // Nächste FahrerID manuell ermitteln, da kein AUTO_INCREMENT gesetzt ist
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

// Lädt alle Rennen, an denen ein Fahrer teilgenommen hat (inkl. aktueller Prämie)
function rennenFuerFahrerLaden($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT n.RennID, r.Datum, r.Startort, n.FahrerPraemie
         FROM nimmtTeil n
         JOIN Rennen r ON n.RennID = r.RennID
         WHERE n.FahrerID = ? AND n.TeamName = ?
         ORDER BY r.Datum DESC"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Setzt die Fahrerprämie für ein bestimmtes Rennen (überschreibt vorhandenen Wert)
function praemieSpeichern($verbindung, $fahrerID, $teamName, $rennID, $praemie)
{
    $stmt = $verbindung->prepare(
        "UPDATE nimmtTeil SET FahrerPraemie = ?
         WHERE FahrerID = ? AND TeamName = ? AND RennID = ?"
    );
    $stmt->execute([$praemie, $fahrerID, $teamName, $rennID]);
}
