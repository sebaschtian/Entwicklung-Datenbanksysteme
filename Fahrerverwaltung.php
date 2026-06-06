<?php
// Autor: Marlies Achterholt
session_start();

// Guard: only logged-in coaches may access this page.
if (!isset($_SESSION['teamchef_login'])) {
    header('Location: index.php');
    exit;
}

require 'Backend/db.inc.php';
require 'Backend/fahrer.inc.php';
require 'Backend/csrf.inc.php';

$teamName = $_SESSION['teamchef_teamname'];
$action   = $_GET['action'] ?? 'liste';
$fehler   = "";
$erfolg   = "";

// ── Save driver (insert or update) ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fahrer_speichern'])) {
    csrfPruefen();
    $fahrerIDRaw      = $_POST['fahrerID'] ?? '';
    $fahrerID         = (int) $fahrerIDRaw;
    $fahrerName       = trim($_POST['fahrerName']);
    $ortName          = trim($_POST['ortName']);
    $plz              = trim($_POST['plz']);
    $strasseHausnummer = trim($_POST['strasseHausnummer']);
    $nummern          = $_POST['telefonnummern'] ?? [];
    $isNeu            = ($fahrerIDRaw === ''); // empty string = new driver; numeric = edit existing

    if (empty($fahrerName) || empty($ortName) || empty($plz) || empty($strasseHausnummer)) {
        $fehler = "Bitte alle Pflichtfelder ausfüllen.";
        $action = 'formular';
    } else {
        try {
            $fahrerID = fahrerSpeichern(
                $verbindung, $fahrerID, $teamName,
                $fahrerName, $ortName, $plz, $strasseHausnummer, $isNeu
            );
            telefonnummernSpeichern($verbindung, $fahrerID, $teamName, $nummern);
            $erfolg = $isNeu ? "Fahrer erfolgreich angelegt." : "Fahrer erfolgreich aktualisiert.";
            $action = 'liste';
        } catch (Exception $e) {
            $fehler = "Fahrer konnte nicht gespeichert werden: " . $e->getMessage();
            $action = 'formular';
        }
    }
}

// ── Delete driver ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fahrer_loeschen'])) {
    csrfPruefen();
    $fahrerID = (int) $_POST['fahrerID'];
    try {
        fahrerLoeschen($verbindung, $fahrerID, $teamName);
        $erfolg = "Fahrer erfolgreich gelöscht.";
    } catch (Exception $e) {
        $fehler = "Fahrer konnte nicht gelöscht werden (evtl. Rennanmeldungen vorhanden).";
    }
    $action = 'liste';
}

// ── Record training session ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['training_speichern'])) {
    csrfPruefen();
    $fahrerIDRaw  = $_POST['fahrerID'] ?? '';
    $fahrerID     = (int) $fahrerIDRaw;
    $datum        = trim($_POST['datum']);
    $gefahreneKM  = (int) $_POST['gefahreneKM'];
    $trainingsziel = trim($_POST['trainingsziel']);

    if ($fahrerIDRaw === '') {
        $fehler = "Bitte einen Fahrer auswählen.";
        $action = 'training';
    } elseif (empty($datum)) {
        $fehler = "Bitte ein Datum eingeben.";
        $action = 'training';
    } elseif ($gefahreneKM <= 0) {
        $fehler = "Bitte gefahrene Kilometer eingeben (mind. 1).";
        $action = 'training';
    } elseif (empty($trainingsziel)) {
        $fehler = "Bitte ein Trainingsziel auswählen. (Sind Trainingsziele in der Datenbank vorhanden?)";
        $action = 'training';
    } else {
        try {
            trainingErfassen($verbindung, $fahrerID, $teamName, $datum, $gefahreneKM, $trainingsziel);
            $erfolg = "Training erfolgreich gespeichert.";
        } catch (Exception $e) {
            $fehler = "An diesem Tag existiert für diesen Fahrer bereits ein Training.";
        }
        $action = 'training';
    }
}

// ── Load driver data for the edit form ────────────────────
$fahrerEdit = null;
$telefonnummernEdit = [];
if ($action === 'formular' && isset($_GET['fahrerID'])) {
    $fahrerEdit = fahrerLadenEinzeln($verbindung, (int) $_GET['fahrerID'], $teamName);
    if (!$fahrerEdit) {
        $fehler = "Fahrer nicht gefunden.";
        $action = 'liste';
    } else {
        $telefonnummernEdit = telefonnummernLaden($verbindung, $fahrerEdit['FahrerID'], $teamName);
    }
}

// ── Load data for all views ───────────────────────────────
$fahrer         = fahrerLaden($verbindung, $teamName);
$trainingsziele = ($action === 'training') ? trainingszieleAbrufen($verbindung) : [];

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fahrerverwaltung</title>
</head>
<body>
    <h1>Fahrerverwaltung – Team: <?= htmlspecialchars($teamName) ?></h1>

    <!-- Navigation -->
    <nav>
        <a href="Fahrerverwaltung.php?action=liste">Fahrerliste</a> |
        <a href="Fahrerverwaltung.php?action=formular">Neuen Fahrer anlegen</a> |
        <a href="Fahrerverwaltung.php?action=training">Training erfassen</a> |
        <a href="Fahrer_Rennanmeldung.php">Rennanmeldung</a> |
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
    <!-- ── Fahrerliste ── -->
    <h2>Fahrerliste</h2>
    <?php if (empty($fahrer)): ?>
        <p>Noch keine Fahrer im Team vorhanden.</p>
    <?php else: ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Ort</th>
                <th>PLZ</th>
                <th>Strasse &amp; Hausnummer</th>
                <th>Aktionen</th>
            </tr>
            <?php foreach ($fahrer as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['FahrerID']) ?></td>
                <td><?= htmlspecialchars($f['FahrerName']) ?></td>
                <td><?= htmlspecialchars($f['OrtName']) ?></td>
                <td><?= htmlspecialchars($f['PLZ']) ?></td>
                <td><?= htmlspecialchars($f['StrasseHausnummer']) ?></td>
                <td>
                    <a href="Fahrerverwaltung.php?action=formular&fahrerID=<?= $f['FahrerID'] ?>">Bearbeiten</a>
                    <!-- Löschen als POST-Formular um CSRF-Angriffe via GET-Link zu vermeiden -->
                    <form action="Fahrerverwaltung.php" method="post" style="display:inline"
                          onsubmit="return confirm('Fahrer wirklich löschen?')">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="fahrerID" value="<?= $f['FahrerID'] ?>">
                        <input type="submit" name="fahrer_loeschen" value="Löschen">
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php elseif ($action === 'formular'): ?>
    <!-- ── Fahrer anlegen / bearbeiten (eine Seite, ein Button) ── -->
    <h2><?= $fahrerEdit ? 'Fahrer bearbeiten' : 'Neuen Fahrer anlegen' ?></h2>
    <form action="Fahrerverwaltung.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <!-- leer = Neueintrag, gesetzt = Änderung (auch ID 0 möglich) -->
        <input type="hidden" name="fahrerID"
               value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['FahrerID']) : '' ?>">

        <?php if ($fahrerEdit): ?>
            <p>Fahrer-ID: <strong><?= htmlspecialchars($fahrerEdit['FahrerID']) ?></strong>
            (kann nicht geändert werden)</p>
        <?php endif; ?>

        <label>Name:
            <input type="text" name="fahrerName" required
                value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['FahrerName']) : (isset($_POST['fahrerName']) ? htmlspecialchars($_POST['fahrerName']) : '') ?>">
        </label><br>

        <label>Strasse &amp; Hausnummer:
            <input type="text" name="strasseHausnummer" required
                value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['StrasseHausnummer']) : (isset($_POST['strasseHausnummer']) ? htmlspecialchars($_POST['strasseHausnummer']) : '') ?>">
        </label><br>

        <label>PLZ:
            <input type="text" name="plz" required maxlength="5"
                value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['PLZ']) : (isset($_POST['plz']) ? htmlspecialchars($_POST['plz']) : '') ?>">
        </label><br>

        <label>Ort:
            <input type="text" name="ortName" required
                value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['OrtName']) : (isset($_POST['ortName']) ? htmlspecialchars($_POST['ortName']) : '') ?>">
        </label><br>

        <!-- Telefonnummern: bis zu 3 Felder -->
        <p>Telefonnummern (optional):</p>
        <?php
        $maxTel = 3;
        for ($i = 0; $i < $maxTel; $i++):
            $val = $telefonnummernEdit[$i] ?? '';
        ?>
            <label>Nr. <?= $i + 1 ?>:
                <input type="text" name="telefonnummern[]"
                    value="<?= htmlspecialchars($val) ?>">
            </label><br>
        <?php endfor; ?>

        <br>
        <input type="submit" name="fahrer_speichern" value="Speichern">
        <a href="Fahrerverwaltung.php?action=liste">Abbrechen</a>
    </form>

    <?php elseif ($action === 'training'): ?>
    <!-- ── Training erfassen ── -->
    <h2>Training erfassen</h2>
    <?php if (empty($fahrer)): ?>
        <p>Keine Fahrer vorhanden. Bitte zuerst Fahrer anlegen.</p>
    <?php else: ?>
    <form action="Fahrerverwaltung.php?action=training" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <label>Fahrer:
            <select name="fahrerID" required>
                <option value="">-- Fahrer wählen --</option>
                <?php foreach ($fahrer as $f): ?>
                    <option value="<?= $f['FahrerID'] ?>"
                        <?= (isset($_POST['fahrerID']) && $_POST['fahrerID'] == $f['FahrerID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['FahrerName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Datum:
            <input type="date" name="datum" required
                value="<?= isset($_POST['datum']) ? htmlspecialchars($_POST['datum']) : '' ?>">
        </label><br>

        <label>Gefahrene Kilometer:
            <input type="number" name="gefahreneKM" min="1" required
                value="<?= isset($_POST['gefahreneKM']) ? htmlspecialchars($_POST['gefahreneKM']) : '' ?>">
        </label><br>

        <label>Trainingsziel:
            <select name="trainingsziel" required>
                <option value="">-- Ziel wählen --</option>
                <?php foreach ($trainingsziele as $ziel): ?>
                    <option value="<?= htmlspecialchars($ziel) ?>"
                        <?= (isset($_POST['trainingsziel']) && $_POST['trainingsziel'] === $ziel) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ziel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <br>
        <input type="submit" name="training_speichern" value="Training speichern">
    </form>
    <?php endif; ?>

    <?php endif; ?>

</body>
</html>