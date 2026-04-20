<?php
// Autor: Sebastian Rieg

// Speichert oder ändert einen Fahrer; gibt die MitarbeiterID zurück
// Wenn Mitarbeiter ID bereits im Team vorhanden ist, wird aktualisiert, ansonsten neu angelegt
function fahrerSpeichern($verbindung, $mitarbeiterID, $teamname, $vorname, $nachname, $ort, $plz, $strasse, $hausnr, $isNeu)
{
    if ($isNeu) {
        $stmt = $verbindung->prepare(
            "INSERT INTO Fahrer (Vorname, Nachname, Ort, PLZ, Strasse, HausNr, Teamname)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$vorname, $nachname, $ort, $plz, $strasse, $hausnr, $teamname]);
        return (int) $verbindung->lastInsertId();
    } else {
        $stmt = $verbindung->prepare(
            "UPDATE Fahrer SET Vorname = ?, Nachname = ?, Ort = ?, PLZ = ?, Strasse = ?, HausNr = ?
             WHERE MitarbeiterID = ? AND Teamname = ?"
        );
        $stmt->execute([$vorname, $nachname, $ort, $plz, $strasse, $hausnr, $mitarbeiterID, $teamname]);
        return $mitarbeiterID;
    }
}

function fahrerLaden($verbindung, $teamname)
{
    $stmt = $verbindung->prepare(
        "SELECT MitarbeiterID, Vorname, Nachname, Ort, PLZ, Strasse, HausNr 
         FROM Fahrer WHERE Teamname = ? ORDER BY Nachname, Vorname"
    );
    $stmt->execute([$teamname]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fahrerLadenEinzeln($verbindung, $mitarbeiterID, $teamname)
{
    $stmt = $verbindung->prepare(
        "SELECT MitarbeiterID, Vorname, Nachname, Ort, PLZ, Strasse, HausNr 
         FROM Fahrer WHERE MitarbeiterID = ? AND Teamname = ?"
    );
    $stmt->execute([$mitarbeiterID, $teamname]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fahrerLoeschen($verbindung, $mitarbeiterID, $teamname)
{
    $verbindung->beginTransaction();
    try {
        $stmt = $verbindung->prepare(
            "DELETE FROM Telefonnummern WHERE MitarbeiterID = ? AND Teamname = ?"
        );
        $stmt->execute([$mitarbeiterID, $teamname]);

        $stmt = $verbindung->prepare(
            "DELETE FROM Fahrer WHERE MitarbeiterID = ? AND Teamname = ?"
        );
        $stmt->execute([$mitarbeiterID, $teamname]);

        $verbindung->commit();
    } catch (Exception $e) {
        $verbindung->rollBack();
        throw $e;
    }
}

// Telefonnummern eines Fahrers laden
function telefonnummernLaden($verbindung, $mitarbeiterID, $teamname)
{
    $stmt = $verbindung->prepare(
        "SELECT Telefonnummer FROM Telefonnummern 
         WHERE MitarbeiterID = ? AND Teamname = ? ORDER BY Telefonnummer"
    );
    $stmt->execute([$mitarbeiterID, $teamname]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Telefonnummern komplett ersetzen (alte raus, neue rein)
function telefonnummernSpeichern($verbindung, $mitarbeiterID, $teamname, array $nummern)
{
    $stmt = $verbindung->prepare(
        "DELETE FROM Telefonnummern WHERE MitarbeiterID = ? AND Teamname = ?"
    );
    $stmt->execute([$mitarbeiterID, $teamname]);

    $stmt = $verbindung->prepare(
        "INSERT INTO Telefonnummern (MitarbeiterID, Teamname, Telefonnummer) VALUES (?, ?, ?)"
    );
    foreach ($nummern as $nr) {
        $nr = trim($nr);
        if ($nr !== '') {
            $stmt->execute([$mitarbeiterID, $teamname, $nr]);
        }
    }
}