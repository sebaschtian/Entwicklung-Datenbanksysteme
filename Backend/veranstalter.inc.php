<?php
// Autor: Sebastian Rieg

// Prüft ob ein Veranstalter bereits existiert
function veranstalterExistiert($verbindung, $veranstalterName)
{
    $stmt = $verbindung->prepare(
        "SELECT VeranstalterName FROM Rennveranstalter WHERE VeranstalterName = ?"
    );
    $stmt->execute([$veranstalterName]);
    return $stmt->rowCount() > 0;
}

// Registriert einen neuen Rennveranstalter
function veranstalterRegistrieren($verbindung, $veranstalterName, $kennwort)
{
    $kennwortHash = password_hash($kennwort, PASSWORD_DEFAULT);

    $stmt = $verbindung->prepare(
        "INSERT INTO Rennveranstalter (VeranstalterName, Kennwort) VALUES (?, ?)"
    );
    $stmt->execute([$veranstalterName, $kennwortHash]);
}

// Prüft Login – gibt true zurück bei Erfolg
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

// ─────────────────────────────────────────
// RENNEN
// ─────────────────────────────────────────

// Legt ein neues Rennen an; RennID wird automatisch vergeben
function rennAnlegen($verbindung, $veranstalterName, $datum, $startort, $streckenKM, $hoehenmeter, $maxSteigung)
{
    $stmt = $verbindung->prepare(
        "INSERT INTO Rennen (Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung, VeranstalterName)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$datum, $startort, $streckenKM, $hoehenmeter, $maxSteigung, $veranstalterName]);
    return (int) $verbindung->lastInsertId();
}

// Lädt alle Rennen
function rennenLaden($verbindung)
{
    $stmt = $verbindung->prepare(
        "SELECT RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung, VeranstalterName
         FROM Rennen ORDER BY Datum DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lädt nur zukünftige Rennen (für Fahreranmeldung)
function rennenLadenZukunft($verbindung)
{
    $stmt = $verbindung->prepare(
        "SELECT RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung
         FROM Rennen WHERE Datum > CURDATE() ORDER BY Datum ASC"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lädt ein einzelnes Rennen anhand der RennID
function rennenLadenEinzeln($verbindung, $rennID)
{
    $stmt = $verbindung->prepare(
        "SELECT RennID, Datum, Startort, StreckenKM, Hoehenmeter, MaxSteigung, VeranstalterName
         FROM Rennen WHERE RennID = ?"
    );
    $stmt->execute([$rennID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ─────────────────────────────────────────
// ERGEBNISSE
// ─────────────────────────────────────────

// Lädt alle Fahrer eines Rennens mit Startnummer, sortiert nach Startnummer
function fahrerZuRennenLaden($verbindung, $rennID)
{
    $stmt = $verbindung->prepare(
        "SELECT nt.Startnummer, nt.FahrerID, nt.TeamName, f.FahrerName
         FROM nimmtTeil nt
         JOIN Fahrer f ON nt.FahrerID = f.FahrerID AND nt.TeamName = f.TeamName
         WHERE nt.RennID = ?
         ORDER BY nt.Startnummer ASC"
    );
    $stmt->execute([$rennID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Speichert das Ergebnis eines Fahrers – einmaliger Vorgang, kein UPDATE
function ergebnisSpeichern($verbindung, $rennID, $fahrerID, $teamName, $platzierung, $gefahreneZeit)
{
    $stmt = $verbindung->prepare(
        "UPDATE nimmtTeil 
         SET Platzierung = ?, gefahreneZeit = ?
         WHERE RennID = ? AND FahrerID = ? AND TeamName = ?
         AND Platzierung IS NULL"  // Sicherheit: nur wenn noch kein Ergebnis
    );
    $stmt->execute([$platzierung, $gefahreneZeit, $rennID, $fahrerID, $teamName]);
}