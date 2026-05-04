<?php
// Autor: Sebastian Rieg
session_start();

if (!isset($_SESSION['teamchef_anmelden'])) {
    header('Location: index.php');
    exit;
}

require 'Backend/db.inc.php';
require 'Backend/fahrer.inc.php';

$teamName = $_SESSION['teamchef_teamname'];
$fehler   = "";
$erfolg   = "";

// ── Fahrer speichern (neu oder ändern) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fahrer_speichern'])) {
    $fahrerID          = (int) ($_POST['fahrerID'] ?? 0);
    $fahrerName        = trim($_POST['fahrerName']);
    $ortName           = trim($_POST['ortName']);
    $plz               = trim($_POST['plz']);
    $strasseHausnummer = trim($_POST['strasseHausnummer']);
    $nummern           = $_POST['telefonnummern'] ?? [];
    $isNeu             = ($fahrerID === 0);

    if (empty($fahrerName) || empty($ortName) || empty($plz) || empty($strasseHausnummer)) {
        $fehler = "Bitte alle Pflichtfelder ausfüllen.";
    } else {
        $fahrerID = fahrerSpeichern($verbindung, $fahrerID, $teamName, $fahrerName, $ortName, $plz, $strasseHausnummer, $isNeu);
        telefonnummernSpeichern($verbindung, $fahrerID, $teamName, $nummern);
        $erfolg = $isNeu ? "Fahrer erfolgreich angelegt." : "Fahrer erfolgreich aktualisiert.";
    }
}

// ── Fahrer löschen ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fahrer_loeschen'])) {
    $fahrerID = (int) $_POST['fahrerID'];
    try {
        fahrerLoeschen($verbindung, $fahrerID, $teamName);
        $erfolg = "Fahrer erfolgreich gelöscht.";
    } catch (Exception $e) {
        $fehler = "Fahrer konnte nicht gelöscht werden (evtl. Rennanmeldungen vorhanden).";
    }
}

// ── Training erfassen ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['training_speichern'])) {
    $fahrerID      = (int) $_POST['fahrerID_training'];
    $datum         = trim($_POST['datum']);
    $gefahreneKM   = (int) $_POST['gefahreneKM'];
    $trainingsziel = trim($_POST['trainingsziel']);

    if (empty($datum) || $gefahreneKM <= 0 || empty($trainingsziel) || $fahrerID === 0) {
        $fehler = "Bitte alle Felder ausfüllen.";
    } else {
        try {
            trainingErfassen($verbindung, $fahrerID, $teamName, $datum, $gefahreneKM, $trainingsziel);
            $erfolg = "Training erfolgreich gespeichert.";
        } catch (Exception $e) {
            $fehler = "An diesem Tag existiert für diesen Fahrer bereits ein Training.";
        }
    }
}

// ── Daten laden ───────────────────────────────────────────
$fahrer         = fahrerLaden($verbindung, $teamName);
$trainingsziele = trainingszieleAbrufen($verbindung);

$fahrerEdit         = null;
$telefonnummernEdit = [];
if (isset($_GET['fahrerID'])) {
    $fahrerEdit = fahrerLadenEinzeln($verbindung, (int) $_GET['fahrerID'], $teamName);
    if ($fahrerEdit) {
        $telefonnummernEdit = telefonnummernLaden($verbindung, $fahrerEdit['FahrerID'], $teamName);
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fahrerverwaltung</title>
</head>
<body>

<h1>Fahrerverwaltung – Team: <?= htmlspecialchars($teamName) ?></h1>
<a href="index.php?logout=1">Abmelden</a> |
<a href="Fahrer_Rennanmeldung.php">Zur Rennanmeldung</a>
<hr>

<?php if ($fehler): ?>
    <p style="color:red;"><?= htmlspecialchars($fehler) ?></p>
<?php endif; ?>
<?php if ($erfolg): ?>
    <p style="color:green;"><?= htmlspecialchars($erfolg) ?></p>
<?php endif; ?>

<!-- ── Fahrerliste ── -->
<h2>Fahrerliste</h2>
<?php if (empty($fahrer)): ?>
    <p>Noch keine Fahrer vorhanden.</p>
<?php else: ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th><th>Name</th><th>Ort</th><th>PLZ</th><th>Strasse &amp; Hausnummer</th><th>Aktionen</th>
        </tr>
        <?php foreach ($fahrer as $f): ?>
        <tr>
            <td><?= htmlspecialchars($f['FahrerID']) ?></td>
            <td><?= htmlspecialchars($f['FahrerName']) ?></td>
            <td><?= htmlspecialchars($f['OrtName']) ?></td>
            <td><?= htmlspecialchars($f['PLZ']) ?></td>
            <td><?= htmlspecialchars($f['StrasseHausnummer']) ?></td>
            <td>
                <a href="Fahrerverwaltung.php?fahrerID=<?= $f['FahrerID'] ?>">Bearbeiten</a>
                <form action="Fahrerverwaltung.php" method="post" style="display:inline">
                    <input type="hidden" name="fahrerID" value="<?= $f['FahrerID'] ?>">
                    <input type="submit" name="fahrer_loeschen" value="Löschen"
                           onclick="return confirm('Fahrer wirklich löschen?')">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<!-- ── Fahrer anlegen / bearbeiten ── -->
<h2><?= $fahrerEdit ? 'Fahrer bearbeiten (ID: ' . htmlspecialchars($fahrerEdit['FahrerID']) . ')' : 'Fahrer anlegen' ?></h2>
<form action="Fahrerverwaltung.php" method="post">
    <input type="hidden" name="fahrerID"
           value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['FahrerID']) : '0' ?>">

    <label>Name:
        <input type="text" name="fahrerName" required
               value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['FahrerName']) : '' ?>">
    </label><br>

    <label>Strasse &amp; Hausnummer:
        <input type="text" name="strasseHausnummer" required
               value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['StrasseHausnummer']) : '' ?>">
    </label><br>

    <label>PLZ:
        <input type="text" name="plz" required maxlength="5"
               value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['PLZ']) : '' ?>">
    </label><br>

    <label>Ort:
        <input type="text" name="ortName" required
               value="<?= $fahrerEdit ? htmlspecialchars($fahrerEdit['OrtName']) : '' ?>">
    </label><br>

    <p>Telefonnummern (optional):</p>
    <?php for ($i = 0; $i < 3; $i++): ?>
        <label>Nr. <?= $i + 1 ?>:
            <input type="text" name="telefonnummern[]"
                   value="<?= htmlspecialchars($telefonnummernEdit[$i] ?? '') ?>">
        </label><br>
    <?php endfor; ?>

    <br>
    <input type="submit" name="fahrer_speichern" value="Speichern">
    <?php if ($fahrerEdit): ?>
        <a href="Fahrerverwaltung.php">Abbrechen</a>
    <?php endif; ?>
</form>

<!-- ── Training erfassen ── -->
<h2>Training erfassen</h2>
<?php if (empty($fahrer)): ?>
    <p>Keine Fahrer vorhanden.</p>
<?php else: ?>
<form action="Fahrerverwaltung.php" method="post">
    <label>Fahrer:
        <select name="fahrerID_training" required>
            <option value="">-- Fahrer wählen --</option>
            <?php foreach ($fahrer as $f): ?>
                <option value="<?= $f['FahrerID'] ?>">
                    <?= htmlspecialchars($f['FahrerID'] . ' @@@ ' . $f['FahrerName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Datum:
        <input type="date" name="datum" required>
    </label><br>

    <label>Kilometer:
        <input type="number" name="gefahreneKM" min="1" required>
    </label><br>

    <label>Trainingsziel:
        <select name="trainingsziel" required>
            <option value="">-- Ziel wählen --</option>
            <?php foreach ($trainingsziele as $ziel): ?>
                <option value="<?= htmlspecialchars($ziel) ?>">
                    <?= htmlspecialchars($ziel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <br>
    <input type="submit" name="training_speichern" value="Training speichern">
</form>
<?php endif; ?>

</body>
</html>