<?php
// Autor: Sebastian Rieg
	include '../Backend/db.inc.php';
    include '../Backend/fahrer.inc.php';
    session_start();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Rennanmeldung Fahrer</title>
</head>
<body>                                          
    <h1>Rennanmeldung Fahrer</h1>

    <form method="post" action="Fahrer_Rennanmeldung.php">

            <!-- Hier werden die Teams aus der Datenbank geladen -->
            <?php
                $sql = "SELECT team_id, teamname FROM team";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row["team_id"] . "'>" . $row["teamname"] . "</option>";
                    }
                } else {
                    echo "<option value=''>Keine Fahrer verfügbar</option>";
                }
            ?>
        </select></label>

        <input type="submit" value="Anmelden">
    </form><?php
// Autor: Sebastian Rieg
session_start();

if (!isset($_SESSION['teamchef_login'])) {
    header('Location: index.php');
    exit;
}

require 'Backend/db.inc.php';
require 'Backend/fahrer.inc.php';
require 'Backend/veranstalter.inc.php';

$teamName = $_SESSION['teamchef_teamname'];
$fehler   = "";
$erfolg   = "";
$action   = $_GET['action'] ?? 'liste';

// ── Fahrer zu Rennen anmelden (Speichern) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fahrer_anmelden'])) {
    $rennID    = (int) $_POST['rennID'];
    $fahrerIDs = $_POST['fahrerIDs'] ?? [];

    $gespeichert = 0;
    foreach ($fahrerIDs as $fahrerID) {
        $fahrerID = (int) $fahrerID;
        if ($fahrerID === 0) continue; // leere Zeilen überspringen

        // Doppelte Einträge überspringen
        $stmtCheck = $verbindung->prepare(
            "SELECT COUNT(*) FROM nimmtTeil WHERE RennID = ? AND FahrerID = ?"
        );
        $stmtCheck->execute([$rennID, $fahrerID]);
        if ($stmtCheck->fetchColumn() > 0) continue;

        // Startnummer wird automatisch vom Trigger vergeben
        $stmt = $verbindung->prepare(
            "INSERT INTO nimmtTeil (RennID, FahrerID, TeamName)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$rennID, $fahrerID, $teamName]);
        $gespeichert++;
    }

    $erfolg = "$gespeichert Fahrer erfolgreich angemeldet.";
    $action = 'liste';
}

// ── Anmeldung kopieren ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kopieren_speichern'])) {
    $quelleRennID = (int) $_POST['quelleRennID'];
    $zielRennID   = (int) $_POST['zielRennID'];

    // Alle Fahrer des Teams aus dem Quellrennen laden
    $stmt = $verbindung->prepare(
        "SELECT FahrerID FROM nimmtTeil
         WHERE RennID = ? AND TeamName = ?"
    );
    $stmt->execute([$quelleRennID, $teamName]);
    $fahrer = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $gespeichert = 0;
    foreach ($fahrer as $fahrerID) {
        // Doppelte überspringen
        $stmtCheck = $verbindung->prepare(
            "SELECT COUNT(*) FROM nimmtTeil WHERE RennID = ? AND FahrerID = ?"
        );
        $stmtCheck->execute([$zielRennID, $fahrerID]);
        if ($stmtCheck->fetchColumn() > 0) continue;

        // Startnummer wieder per Trigger
        $stmtInsert = $verbindung->prepare(
            "INSERT INTO nimmtTeil (RennID, FahrerID, TeamName)
             VALUES (?, ?, ?)"
        );
        $stmtInsert->execute([$zielRennID, $fahrerID, $teamName]);
        $gespeichert++;
    }

    $erfolg = "Anmeldung kopiert – $gespeichert Fahrer übertragen.";
    $action = 'liste';
}

// ── Daten laden ───────────────────────────────────────────
$zukuenftigeRennen = rennenLadenZukunft($verbindung);
$fahrer            = fahrerLaden($verbindung, $teamName);

// Anzahl Zeilen für Anmeldetabelle
$anzahlZeilen = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['anzahl_bestaetigen'])) {
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
            <th>Aktionen</th>
        </tr>
        <?php foreach ($zukuenftigeRennen as $rennen): ?>
        <tr>
            <td><?= htmlspecialchars($rennen['RennID']) ?></td>
            <td><?= htmlspecialchars($rennen['Datum']) ?></td>
            <td><?= htmlspecialchars($rennen['Startort']) ?></td>
            <td><?= htmlspecialchars($rennen['StreckenKM']) ?></td>
            <td><?= htmlspecialchars($rennen['Hoehenmeter']) ?></td>
            <td><?= htmlspecialchars($rennen['MaxSteigung']) ?>%</td>
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
if (!$rennen) {
    echo '<p style="color:red;">Rennen nicht gefunden.</p>';
} elseif ($anzahlZeilen === 0):
?>
<h2>Anmeldung für: <?= htmlspecialchars($rennen['Startort']) ?> (<?= htmlspecialchars($rennen['Datum']) ?>)</h2>
<form action="Fahrer_Rennanmeldung.php?action=anmelden" method="post">
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
    <input type="hidden" name="rennID" value="<?= $gewaehlteRennID ?>">
    <table border="1" cellpadding="5">
        <tr>
            <th>Zeile</th>
            <th>Fahrer (ID @@@ Name)</th>
        </tr>
        <?php
        // Gewünschte Anzahl + 5 zusätzliche leere Zeilen
        $gesamtZeilen = $anzahlZeilen + 5;
        for ($i = 0; $i < $gesamtZeilen; $i++):
        ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td>
                <select name="fahrerIDs[]">
                    <option value="0">-- kein Fahrer --</option>
                    <?php foreach ($fahrer as $f): ?>
                        <option value="<?= $f['FahrerID'] ?>">
                            <?= htmlspecialchars($f['FahrerID'] . ' @@@ ' . $f['FahrerName']) ?>
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
<