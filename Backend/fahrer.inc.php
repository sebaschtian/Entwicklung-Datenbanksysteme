<?php
// ── Drivers ───────────────────────────────────────────────
// Autor: Sebastian Rieg
// INSERT new driver (FahrerID assigned by trigger) or UPDATE existing one; returns FahrerID.
function fahrerSpeichern($verbindung, $fahrerID, $teamName, $fahrerName, $ortName, $plz, $strasseHausnummer, $isNeu)
{
    if ($isNeu) {
        $stmt = $verbindung->prepare(
            "INSERT INTO Fahrer (FahrerName, OrtName, PLZ, StrasseHausnummer, TeamName)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$fahrerName, $ortName, $plz, $strasseHausnummer, $teamName]);
        $stmt2 = $verbindung->prepare("SELECT MAX(FahrerID) FROM Fahrer WHERE TeamName = ?");
        $stmt2->execute([$teamName]);
        return (int) $stmt2->fetchColumn(0);
    } else {
        $stmt = $verbindung->prepare(
            "UPDATE Fahrer SET FahrerName = ?, OrtName = ?, PLZ = ?, StrasseHausnummer = ?
             WHERE FahrerID = ? AND TeamName = ?"
        );
        $stmt->execute([$fahrerName, $ortName, $plz, $strasseHausnummer, $fahrerID, $teamName]);
        return $fahrerID;
    }
}

// Returns all drivers of a team, ordered by name.
function fahrerLaden($verbindung, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer
         FROM Fahrer WHERE TeamName = ? ORDER BY FahrerName"
    );
    $stmt->execute([$teamName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Returns a single driver by FahrerID and TeamName, or false if not found.
function fahrerLadenEinzeln($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT FahrerID, FahrerName, OrtName, PLZ, StrasseHausnummer
         FROM Fahrer WHERE FahrerID = ? AND TeamName = ?"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Deletes a driver and all related data (phone numbers, training, race entries) via stored procedure.
function fahrerLoeschen($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare("CALL FahrerLoeschen(?, ?)");
    $stmt->execute([$fahrerID, $teamName]);
}

// ── Phone numbers ─────────────────────────────────────────

// Returns all phone numbers for a driver.
function telefonnummernLaden($verbindung, $fahrerID, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT Telefonnummer FROM Telefonnummer
         WHERE FahrerID = ? AND TeamName = ? ORDER BY Telefonnummer"
    );
    $stmt->execute([$fahrerID, $teamName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Replaces all phone numbers for a driver atomically (delete + re-insert in one transaction).
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

// ── Training ──────────────────────────────────────────────

// Returns all training goals from the lookup table.
function trainingszieleAbrufen($verbindung)
{
    return $verbindung->query(
        "SELECT Trainingsziel FROM Trainingsziel ORDER BY Trainingsziel"
    )->fetchAll(PDO::FETCH_COLUMN);
}

// Inserts a training session; DB enforces one entry per driver per day via primary key.
function trainingErfassen($verbindung, $fahrerID, $teamName, $datum, $gefahreneKM, $trainingsziel)
{
    $stmt = $verbindung->prepare(
        "INSERT INTO Training (FahrerID, TeamName, Datum, gefahreneKM, Trainingsziel)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$fahrerID, $teamName, $datum, $gefahreneKM, $trainingsziel]);
}

// ── Race registrations ────────────────────────────────────

// Returns a map of RaceID → ["Name (Nr. X)", ...] for all upcoming races this team is registered in.
function angemeldteFahrerProRennen($verbindung, $teamName)
{
    $stmt = $verbindung->prepare(
        "SELECT n.RennID, n.Startnummer, f.FahrerName
         FROM nimmtTeil n
         JOIN Fahrer f ON n.FahrerID = f.FahrerID AND n.TeamName = f.TeamName
         JOIN Rennen r ON n.RennID = r.RennID
         WHERE n.TeamName = ? AND r.Datum > CURDATE()
         ORDER BY n.RennID, n.Startnummer"
    );
    $stmt->execute([$teamName]);
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $zeile) {
        $map[$zeile['RennID']][] = $zeile['FahrerName'] . ' (Nr. ' . $zeile['Startnummer'] . ')';
    }
    return $map;
}
