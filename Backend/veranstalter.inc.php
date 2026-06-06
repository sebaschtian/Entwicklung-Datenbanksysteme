<?php
// Autor: Marlies Achterholt
// ── Organizer (Rennveranstalter) ──────────────────────────

// Returns true if an organizer with this name already exists.
function veranstalterExistiert($verbindung, $veranstalterName)
{
    $stmt = $verbindung->prepare(
        "SELECT VeranstalterName FROM Rennveranstalter WHERE VeranstalterName = ?"
    );
    $stmt->execute([$veranstalterName]);
    return $stmt->rowCount() > 0;
}

// Registers a new organizer with a bcrypt-hashed password.
function veranstalterRegistrieren($verbindung, $veranstalterName, $kennwort)
{
    $kennwortHash = password_hash($kennwort, PASSWORD_DEFAULT);

    $stmt = $verbindung->prepare(
        "INSERT INTO Rennveranstalter (VeranstalterName, Kennwort) VALUES (?, ?)"
    );
    $stmt->execute([$veranstalterName, $kennwortHash]);
}

// Verifies login credentials; returns true on success.
function veranstalterLogin($verbindung, $veranstalterName, $kennwort)
{
    $stmt = $verbindung->prepare(
        "SELECT Kennwort FROM Rennveranstalter WHERE VeranstalterName = ?"
    );
    $stmt->execute([$veranstalterName]);
    $zeile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$zeile) return false;
    return password_verify($kennwort, $zeile['Kennwort']);
}

// ── Races (Rennen) ────────────────────────────────────────

// Deletes a race and all its registrations in a single transaction.
function rennLoeschen($verbindung, $rennID, $veranstalterName)
{
    $verbindung->beginTransaction();
    try {
        $stmt = $verbindung->prepare(
            "DELETE FROM nimmtTeil WHERE RennID = ?"
        );
        $stmt->execute([$rennID]);

        $stmt = $verbindung->prepare(
            "DELETE FROM Rennen WHERE RennID = ? AND VeranstalterName = ?"
        );
        $stmt->execute([$rennID, $veranstalterName]);

        $verbindung->commit();
    } catch (Exception $e) {
        $verbindung->rollBack();
        throw $e;
    }
}

// Inserts a new race; RaceID is computed manually (no AUTO_INCREMENT on this table).
function rennAnlegen($verbindung, $veranstalterName, $datum, $startort, $streckenKM, $hoehenmeter, $maxSteigung)
{
    $ergebnis   = $verbindung->query("SELECT COALESCE(MAX(RennID), 0) + 1 FROM Rennen");
    $naechsteID = max(1, (int) $ergebnis->fetchColumn(0));
    $stmt = $verbindung->prepare(
        "INSERT INTO Rennen (RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung, VeranstalterName)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$naechsteID, $datum, $startort, $streckenKM, $hoehenmeter, $maxSteigung, $veranstalterName]);
    return $naechsteID;
}

// Returns all races ordered by date descending.
function rennenLaden($verbindung)
{
    return $verbindung->query(
        "SELECT RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung, VeranstalterName
         FROM Rennen ORDER BY Datum DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// Returns only future races ordered by date ascending.
function rennenLadenZukunft($verbindung)
{
    return $verbindung->query(
        "SELECT RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung
         FROM Rennen WHERE Datum > CURDATE() ORDER BY Datum ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// Returns a single race by RaceID, or false if not found.
function rennenLadenEinzeln($verbindung, $rennID)
{
    $stmt = $verbindung->prepare(
        "SELECT RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung, VeranstalterName
         FROM Rennen WHERE RennID = ?"
    );
    $stmt->execute([$rennID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ── Results (Ergebnisse) ──────────────────────────────────

// Returns all registered drivers for a race with their current results, ordered by start number.
function fahrerZuRennenLaden($verbindung, $rennID)
{
    $stmt = $verbindung->prepare(
        "SELECT nt.Startnummer, nt.FahrerID, nt.TeamName, f.FahrerName,
                nt.Platzierung, nt.gefahreneZeit
         FROM nimmtTeil nt
         JOIN Fahrer f ON nt.FahrerID = f.FahrerID AND nt.TeamName = f.TeamName
         WHERE nt.RennID = ?
         ORDER BY nt.Startnummer ASC"
    );
    $stmt->execute([$rennID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Returns race results ordered by placement.
function ergebnisseLaden($verbindung, $rennID)
{
    $stmt = $verbindung->prepare(
        "SELECT nt.Startnummer, nt.FahrerID, nt.TeamName, f.FahrerName,
                nt.Platzierung, nt.gefahreneZeit
         FROM nimmtTeil nt
         JOIN Fahrer f ON nt.FahrerID = f.FahrerID AND nt.TeamName = f.TeamName
         WHERE nt.RennID = ?
         ORDER BY nt.Platzierung ASC, nt.Startnummer ASC"
    );
    $stmt->execute([$rennID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Writes a result for one driver; only updates rows where no result has been saved yet.
function ergebnisSpeichern($verbindung, $rennID, $fahrerID, $teamName, $platzierung, $gefahreneZeit)
{
    $stmt = $verbindung->prepare(
        "UPDATE nimmtTeil
         SET Platzierung = ?, gefahreneZeit = ?
         WHERE RennID = ? AND FahrerID = ? AND TeamName = ?
         AND Platzierung IS NULL"
    );
    $stmt->execute([$platzierung, $gefahreneZeit, $rennID, $fahrerID, $teamName]);
}
