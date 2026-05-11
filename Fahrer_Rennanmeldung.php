<?php
// Autor: Sebastian Rieg
session_start();

if (!isset($_SESSION['teamchef_login'])) {
    header('Location: index.php');
    exit;
}

require 'Backend/db.inc.php';
require 'Backend/csrf.inc.php';
require 'Backend/fahrer.inc.php';
require 'Backend/veranstalter.inc.php';

$teamName = $_SESSION['teamchef_teamname'];
$fehler   = "";
$erfolg   = "";
$action   = $_GET['action'] ?? 'liste';

// ── Fahrer zu Rennen anmelden (Speichern) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fahrer_anmelden'])) {
    csrfPruefen();
    $rennID    = (int) $_POST['rennID'];
    $fahrerIDs = $_POST['fahrerIDs'] ?? [];

    $gespeichert = 0;
    $stmtCheck  = $verbindung->prepare(
        "SELECT COUNT(*) FROM nimmtTeil WHERE RennID = ? AND FahrerID = ? AND TeamName = ?"
    );
    $stmtInsert = $verbindung->prepare(
        "INSERT INTO nimmtTeil (RennID, FahrerID, TeamName) VALUES (?, ?, ?)"
    );
    $verbindung->beginTransaction();
    try {
        foreach ($fahrerIDs as $fahrerIDRaw) {
            if ($fahrerIDRaw === '') continue;
            $fahrerID = (int) $fahrerIDRaw;

            $stmtCheck->execute([$rennID, $fahrerID, $teamName]);
            if ($stmtCheck->fetchColumn() > 0) continue;

            $stmtInsert->execute([$rennID, $fahrerID, $teamName]);
            $gespeichert++;
        }
        $verbindung->commit();
        $erfolg = "$gespeichert Fahrer erfolgreich angemeldet.";
    } catch (Exception $e) {
        $verbindung->rollBack();
        $fehler = "Anmeldung fehlgeschlagen. Prüfe ob der Trigger für Startnummern existiert. (Fehler: " . $e->getMessage() . ")";
    }
    $action = 'liste';
}

// ── Anmeldung kopieren ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kopieren_speichern'])) {
    csrfPruefen();
    $quelleRennID = (int) $_POST['quelleRennID'];
    $zielRennID   = (int) $_POST['zielRennID'];

    $stmtQuelle = $verbindung->prepare(
        "SELECT FahrerID FROM nimmtTeil WHERE RennID = ? AND TeamName = ?"
    );
    $stmtQuelle->execute([$quelleRennID, $teamName]);
    $quellFahrer = $stmtQuelle->fetchAll(PDO::FETCH_COLUMN);

    $stmtCheck  = $verbindung->prepare(
        "SELECT COUNT(*) FROM nimmtTeil WHERE RennID = ? AND FahrerID = ? AND TeamName = ?"
    );
    $stmtInsert = $verbindung->prepare(
        "INSERT INTO nimmtTeil (RennID, FahrerID, TeamName) VALUES (?, ?, ?)"
    );

    $gespeichert = 0;
    $verbindung->beginTransaction();
    try {
        foreach ($quellFahrer as $fahrerID) {
            $stmtCheck->execute([$zielRennID, $fahrerID, $teamName]);
            if ($stmtCheck->fetchColumn() > 0) continue;

            $stmtInsert->execute([$zielRennID, $fahrerID, $teamName]);
            $gespeichert++;
        }
        $verbindung->commit();
        $erfolg = "Anmeldung kopiert – $gespeichert Fahrer übertragen.";
    } catch (Exception $e) {
        $verbindung->rollBack();
        $fehler = "Kopieren fehlgeschlagen: " . $e->getMessage();
    }
    $action = 'liste';
}

// ── Daten laden ───────────────────────────────────────────
$zukuenftigeRennen  = rennenLadenZukunft($verbindung);
$fahrer             = fahrerLaden($verbindung, $teamName);
$anmeldungenProRenn = angemeldteFahrerProRennen($verbindung, $teamName);

// Anzahl Zeilen für Anmeldetabelle
$anzahlZeilen = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['anzahl_bestaetigen'])) {
    csrfPruefen();
    $anzahlZeilen = max(0, (int) $_POST['anzahlFahrer']);
    $action = 'anmelden';
}

$gewaehlteRennID = (int) ($_GET['rennID'] ?? $_POST['rennID'] ?? 0);
$quelleRennID    = (int) ($_GET['quelleRennID'] ?? 0);
?>


<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Rennanmeldung</title>
    </head>
<body>

<h1>Rennanmeldung – Team: <?= htmlspecialchars($teamName) ?></h1>
<a href="index.php?logout=1">Abmelden</a> |
<a href="Fahrerverwaltung.php">Zur Fahrerverwaltung</a>
<hr>

<?php if ($fehler): ?>
    <p style="color:red;"><?= htmlspecialchars($fehler) ?></p>
<?php endif; ?>
<?php if ($erfolg): ?>
    <p style="color:green;"><?= htmlspecialchars($erfolg) ?></p>
<?php endif; ?>

<?php if ($action === 'liste'): ?>
<!-- ── Liste zukünftiger Rennen ── -->
<h2>Zukünftige Rennen</h2>
<?php if (empty($zukuenftigeRennen)): ?>
    <p>Keine zukünftigen Rennen vorhanden.</p>
<?php else: ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Datum</th>
            <th>Startort</th>
            <th>km</th>
            <th>Höhenmeter</th>
            <th>Max. Steigung</th>
            <th>Angemeldete Fahrer</th>
            <th>Aktionen</th>
        </tr>
        <?php foreach ($zukuenftigeRennen as $rennen): ?>
        <?php $angemeldete = $anmeldungenProRenn[$rennen['RennID']] ?? []; ?>
        <tr>
            <td><?= htmlspecialchars($rennen['RennID']) ?></td>
            <td><?= htmlspecialchars($rennen['Datum']) ?></td>
            <td><?= htmlspecialchars($rennen['Startort']) ?></td>
            <td><?= htmlspecialchars($rennen['StreckenKM']) ?></td>
            <td><?= htmlspecialchars($rennen['Hoehenmeter']) ?></td>
            <td><?= htmlspecialchars($rennen['MaxSteigung']) ?>%</td>
            <td>
                <?php if (empty($angemeldete)): ?>
                    <em>Keine</em>
                <?php else: ?>
                    <?= implode(', ', array_map('htmlspecialchars', $angemeldete)) ?>
                <?php endif; ?>
            </td>
            <td>
                <a href="Fahrer_Rennanmeldung.php?action=anmelden&rennID=<?= $rennen['RennID'] ?>">Anmelden</a>
                &nbsp;
                <a href="Fahrer_Rennanmeldung.php?action=kopieren&quelleRennID=<?= $rennen['RennID'] ?>">Kopieren</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php elseif ($action === 'anmelden'): ?>
<!-- ── Schritt 1: Anzahl Fahrer wählen ── -->
<?php
$rennen = rennenLadenEinzeln($verbindung, $gewaehlteRennID);
if (!$rennen):
    echo '<p style="color:red;">Rennen nicht gefunden.</p>';
    elseif ($anzahlZeilen === 0):
?>
<h2>Anmeldung für: <?= htmlspecialchars($rennen['Startort']) ?> (<?= htmlspecialchars($rennen['Datum']) ?>)</h2>
<form action="Fahrer_Rennanmeldung.php?action=anmelden" method="post">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="rennID" value="<?= $gewaehlteRennID ?>">
    <label>Anzahl anzumeldender Fahrer:
        <input type="number" name="anzahlFahrer" min="1" max="<?= count($fahrer) ?>" required>
    </label>
    <input type="submit" name="anzahl_bestaetigen" value="Weiter">
</form>
<a href="Fahrer_Rennanmeldung.php">Zurück</a>

<?php else: ?>
<!-- ── Schritt 2: Fahrer per Combobox auswählen ── -->
<h2>Fahrer auswählen für: <?= htmlspecialchars($rennen['Startort']) ?> (<?= htmlspecialchars($rennen['Datum']) ?>)</h2>
<form action="Fahrer_Rennanmeldung.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="rennID" value="<?= $gewaehlteRennID ?>">
    <table border="1" cellpadding="5">
        <tr>
            <th>Zeile</th>
            <th>Fahrer</th>
        </tr>
        <?php for ($i = 0; $i < $anzahlZeilen; $i++): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td>
                <select name="fahrerIDs[]">
                    <option value="">-- kein Fahrer --</option>
                    <?php foreach ($fahrer as $f): ?>
                        <option value="<?= $f['FahrerID'] ?>">
                            <?= htmlspecialchars($f['FahrerName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php endfor; ?>
    </table>
    <br>
    <input type="submit" name="fahrer_anmelden" value="Alle Fahrer anmelden">
</form>
<a href="Fahrer_Rennanmeldung.php">Zurück</a>
<?php endif; ?>

<?php elseif ($action === 'kopieren'): ?>
<!-- ── Anmeldung kopieren ── -->
<?php $quelleRennen = rennenLadenEinzeln($verbindung, $quelleRennID); ?>
<h2>Anmeldung kopieren von: <?= htmlspecialchars($quelleRennen['Startort'] ?? '?') ?> (<?= htmlspecialchars($quelleRennen['Datum'] ?? '?') ?>)</h2>
<p>Wähle das Zielrennen, in das die Fahrer übertragen werden sollen:</p>

<?php
$zielRennen = array_filter($zukuenftigeRennen, fn($r) => $r['RennID'] != $quelleRennID);
if (empty($zielRennen)):
?>
    <p>Keine weiteren zukünftigen Rennen vorhanden.</p>
<?php else: ?>
<form action="Fahrer_Rennanmeldung.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="quelleRennID" value="<?= $quelleRennID ?>">
    <label>Zielrennen:
        <select name="zielRennID" required>
            <option value="">-- Rennen wählen --</option>
            <?php foreach ($zielRennen as $r): ?>
                <option value="<?= $r['RennID'] ?>">
                    <?= htmlspecialchars($r['RennID'] . ' – ' . $r['Startort'] . ' (' . $r['Datum'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <input type="submit" name="kopieren_speichern" value="Anmeldung kopieren">
</form>
<?php endif; ?>
<a href="Fahrer_Rennanmeldung.php">Zurück</a>

<?php endif; ?>

</body>
</html>