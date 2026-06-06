<?php
// Autor: Marlies Achterholt
session_start();

// Guard: only logged-in organizers may access this page.
if (!isset($_SESSION['veranstalter_name'])) {
    header('Location: index.php');
    exit;
}

require 'Backend/db.inc.php';
require 'Backend/csrf.inc.php';
require 'Backend/veranstalter.inc.php';

$veranstalterName = $_SESSION['veranstalter_name'];
$action           = $_GET['action'] ?? 'liste';
$fehler           = "";
$erfolg           = "";

// ── Create new race ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rennen_speichern'])) {
    csrfPruefen();
    $datum       = trim($_POST['datum']);
    $startort    = trim($_POST['startort']);
    $streckenKM  = trim($_POST['streckenKM']);
    $hoehenmeter = trim($_POST['hoehenmeter']);
    $maxSteigung = trim($_POST['maxSteigung']);

    if (empty($datum) || empty($startort) || empty($streckenKM) || empty($hoehenmeter) || empty($maxSteigung)) {
        $fehler = "Bitte alle Felder ausfüllen.";
        $action = 'formular';
    } else {
        try {
            rennAnlegen($verbindung, $veranstalterName, $datum, $startort, $streckenKM, $hoehenmeter, $maxSteigung);
            $erfolg = "Rennen erfolgreich angelegt.";
            $action = 'liste';
        } catch (Exception $e) {
            $fehler = "Rennen konnte nicht gespeichert werden.";
            $action = 'formular';
        }
    }
}

// ── Delete race ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rennen_loeschen'])) {
    csrfPruefen();
    $rennID = (int) $_POST['rennID'];
    try {
        rennLoeschen($verbindung, $rennID, $veranstalterName);
        $erfolg = "Rennen erfolgreich gelöscht.";
    } catch (Exception $e) {
        $fehler = "Rennen konnte nicht gelöscht werden: " . $e->getMessage();
    }
    $action = 'liste';
}

// ── Save race results ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ergebnis_speichern'])) {
    csrfPruefen();
    $rennID     = (int) $_POST['rennID'];
    $ergebnisse = $_POST['ergebnisse'] ?? [];

    $verbindung->beginTransaction();
    try {
        foreach ($ergebnisse as $eintrag) {
            $fahrerID    = (int) $eintrag['fahrerID'];
            $teamName    = trim($eintrag['teamName']);
            $platzierung = trim($eintrag['platzierung']);
            $zeit        = trim($eintrag['zeit']);

            if ($platzierung !== '' && $zeit !== '') {
                ergebnisSpeichern($verbindung, $rennID, $fahrerID, $teamName, (int) $platzierung, $zeit);
            }
        }
        $verbindung->commit();
        $erfolg = "Ergebnisse erfolgreich gespeichert.";
    } catch (Exception $e) {
        $verbindung->rollBack();
        $fehler = "Ergebnisse konnten nicht gespeichert werden: " . $e->getMessage();
    }
    $action = 'ergebnis';
}

// ── Load data for all views ───────────────────────────────
$rennen     = rennenLaden($verbindung);
$rennEdit   = null;
$fahrer     = [];

// Load race + drivers when entering the results view via GET or staying after a POST save.
if ($action === 'ergebnis' && isset($_GET['rennID'])) {
    $rennID   = (int) $_GET['rennID'];
    $rennEdit = rennenLadenEinzeln($verbindung, $rennID);
    if (!$rennEdit) {
        $fehler = "Rennen nicht gefunden.";
        $action = 'liste';
    } else {
        $fahrer = fahrerZuRennenLaden($verbindung, $rennID);
    }
} elseif ($action === 'ergebnis' && isset($_POST['rennID'])) {
    $rennID   = (int) $_POST['rennID'];
    $rennEdit = rennenLadenEinzeln($verbindung, $rennID);
    $fahrer   = fahrerZuRennenLaden($verbindung, $rennID);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Rennveranstalter</title>
</head>
<body>
    <h1>Rennveranstalter – <?= htmlspecialchars($veranstalterName) ?></h1>

    <!-- Navigation -->
    <nav>
        <a href="Rennveranstalter.php?action=liste">Rennenliste</a> |
        <a href="Rennveranstalter.php?action=formular">Neues Rennen anlegen</a> |
        <a href="Rennergebnisse.php">Ergebnisauswertung</a> |
        <a href="index.php?logout=1">Abmelden</a>
    </nav>
    <hr>

    <!-- Rückmeldungen -->
    <?php if ($fehler): ?>
        <p style="color:red;"><?= htmlspecialchars($fehler) ?></p>
    <?php endif; ?>
    <?php if ($erfolg): ?>
        <p style="color:green;"><?= htmlspecialchars($erfolg) ?></p>
    <?php endif; ?>

    <?php if ($action === 'liste'): ?>
    <!-- ── Rennenliste ── -->
    <h2>Rennenliste</h2>
    <?php if (empty($rennen)): ?>
        <p>Noch keine Rennen vorhanden.</p>
    <?php else: ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>RennID</th>
                <th>Datum</th>
                <th>Startort</th>
                <th>Strecke (km)</th>
                <th>Höhenmeter</th>
                <th>Max. Steigung (%)</th>
                <th>Veranstalter</th>
                <th>Aktion</th>
            </tr>
            <?php foreach ($rennen as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['RennID']) ?></td>
                <td><?= htmlspecialchars($r['Datum']) ?></td>
                <td><?= htmlspecialchars($r['Startort']) ?></td>
                <td><?= htmlspecialchars($r['StreckenKM']) ?></td>
                <td><?= htmlspecialchars($r['Hoehenmeter']) ?></td>
                <td><?= htmlspecialchars($r['MaxSteigung']) ?></td>
                <td><?= htmlspecialchars($r['VeranstalterName']) ?></td>
                <td>
                    <a href="Rennveranstalter.php?action=ergebnis&rennID=<?= $r['RennID'] ?>">
                        Ergebnisse erfassen
                    </a>
                    <?php if ($r['VeranstalterName'] === $veranstalterName): ?>
                    &nbsp;
                    <form action="Rennveranstalter.php" method="post" style="display:inline"
                          onsubmit="return confirm('Rennen wirklich löschen? Alle Anmeldungen und Ergebnisse gehen verloren.')">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="rennID" value="<?= $r['RennID'] ?>">
                        <input type="submit" name="rennen_loeschen" value="Löschen">
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php elseif ($action === 'formular'): ?>
    <!-- ── Rennen anlegen ── -->
    <h2>Neues Rennen anlegen</h2>
    <form action="Rennveranstalter.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <label>Datum:
            <input type="date" name="datum" required
                value="<?= isset($_POST['datum']) ? htmlspecialchars($_POST['datum']) : '' ?>">
        </label><br>

        <label>Startort:
            <input type="text" name="startort" required
                value="<?= isset($_POST['startort']) ? htmlspecialchars($_POST['startort']) : '' ?>">
        </label><br>

        <label>Streckenlänge (km):
            <input type="number" name="streckenKM" min="1" required
                value="<?= isset($_POST['streckenKM']) ? htmlspecialchars($_POST['streckenKM']) : '' ?>">
        </label><br>

        <label>Höhenmeter:
            <input type="number" name="hoehenmeter" min="0" required
                value="<?= isset($_POST['hoehenmeter']) ? htmlspecialchars($_POST['hoehenmeter']) : '' ?>">
        </label><br>

        <label>Maximale Steigung (%):
            <input type="number" name="maxSteigung" min="0" max="99" required
                value="<?= isset($_POST['maxSteigung']) ? htmlspecialchars($_POST['maxSteigung']) : '' ?>">
        </label><br>

        <br>
        <input type="submit" name="rennen_speichern" value="Rennen anlegen">
        <a href="Rennveranstalter.php?action=liste">Abbrechen</a>
    </form>

    <?php elseif ($action === 'ergebnis'): ?>
    <!-- ── Ergebnisse erfassen ── -->
    <h2>Ergebnisse erfassen</h2>

    <?php if (!$rennEdit): ?>
        <p>Bitte ein Rennen aus der <a href="Rennveranstalter.php?action=liste">Rennenliste</a> wählen.</p>
    <?php elseif (empty($fahrer)): ?>
        <p>Keine Fahrer für dieses Rennen angemeldet.</p>
    <?php else: ?>
        <p>
            <strong>Rennen:</strong> <?= htmlspecialchars($rennEdit['Startort']) ?>
            am <?= htmlspecialchars($rennEdit['Datum']) ?>
        </p>

        <form action="Rennveranstalter.php?action=ergebnis" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="rennID" value="<?= htmlspecialchars($rennEdit['RennID']) ?>">

            <table border="1" cellpadding="5">
                <tr>
                    <th>Startnummer</th>
                    <th>Fahrer</th>
                    <th>Team</th>
                    <th>Platzierung</th>
                    <th>Fahrzeit (Sekunden)</th>
                </tr>
                <?php foreach ($fahrer as $i => $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['Startnummer']) ?></td>
                    <td><?= htmlspecialchars($f['FahrerName']) ?></td>
                    <td><?= htmlspecialchars($f['TeamName']) ?></td>
                    <td>
                        <!-- Hidden fields damit FahrerID und TeamName mit übertragen werden -->
                        <input type="hidden" name="ergebnisse[<?= $i ?>][fahrerID]"
                               value="<?= htmlspecialchars($f['FahrerID']) ?>">
                        <input type="hidden" name="ergebnisse[<?= $i ?>][teamName]"
                               value="<?= htmlspecialchars($f['TeamName']) ?>">
                        <?php if ($f['Platzierung'] !== null): ?>
                            <!-- Bereits gespeichert – nur anzeigen -->
                            <?= htmlspecialchars($f['Platzierung']) ?>
                            <input type="hidden" name="ergebnisse[<?= $i ?>][platzierung]" value="">
                        <?php else: ?>
                            <input type="number" name="ergebnisse[<?= $i ?>][platzierung]" min="1">
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['gefahreneZeit'] !== null): ?>
                            <!-- Bereits gespeichert – nur anzeigen -->
                            <?= htmlspecialchars($f['gefahreneZeit']) ?>s
                            <input type="hidden" name="ergebnisse[<?= $i ?>][zeit]" value="">
                        <?php else: ?>
                            <input type="number" name="ergebnisse[<?= $i ?>][zeit]" min="1">
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <br>
            <input type="submit" name="ergebnis_speichern" value="Ergebnisse speichern">
        </form>
    <?php endif; ?>

    <?php endif; ?>

</body>
</html>