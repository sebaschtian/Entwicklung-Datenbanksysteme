<?php
// Autor: Marlies Achterholt
session_start();

// Guard: only logged-in organizers may access this page.
if (!isset($_SESSION['veranstalter_name'])) {
    header('Location: index.php');
    exit;
}

require 'Backend/db.inc.php';
require 'Backend/veranstalter.inc.php';

$veranstalterName = $_SESSION['veranstalter_name'];
$fehler           = "";

// Load all races for the selection table; load results only when a rennID is given.
$alleRennen  = rennenLaden($verbindung);
$rennID      = isset($_GET['rennID']) ? (int) $_GET['rennID'] : null;
$rennEdit    = null;
$ergebnisse  = [];

if ($rennID !== null) {
    $rennEdit   = rennenLadenEinzeln($verbindung, $rennID);
    if (!$rennEdit) {
        $fehler = "Rennen nicht gefunden.";
        $rennID = null;
    } else {
        $ergebnisse = ergebnisseLaden($verbindung, $rennID);
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Rennergebnisse</title>
</head>
<body>

<h1>Rennergebnisse – <?= htmlspecialchars($veranstalterName) ?></h1>

<nav>
    <a href="Rennveranstalter.php?action=liste">Rennenliste</a> |
    <a href="Rennveranstalter.php?action=formular">Neues Rennen anlegen</a> |
    <a href="Rennergebnisse.php">Ergebnisauswertung</a> |
    <a href="index.php?logout=1">Abmelden</a>
</nav>
<hr>

<?php if ($fehler): ?>
    <p style="color:red;"><?= htmlspecialchars($fehler) ?></p>
<?php endif; ?>

<!-- ── Rennen wählen ── -->
<h2>Rennen auswählen</h2>
<?php if (empty($alleRennen)): ?>
    <p>Keine Rennen vorhanden.</p>
<?php else: ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Datum</th>
            <th>Startort</th>
            <th>Strecke (km)</th>
            <th>Aktion</th>
        </tr>
        <?php foreach ($alleRennen as $r): ?>
        <tr <?= ($rennID === (int) $r['RennID']) ? 'style="background:#eef;"' : '' ?>>
            <td><?= htmlspecialchars($r['RennID']) ?></td>
            <td><?= htmlspecialchars($r['Datum']) ?></td>
            <td><?= htmlspecialchars($r['Startort']) ?></td>
            <td><?= htmlspecialchars($r['StreckenKM']) ?></td>
            <td>
                <a href="Rennergebnisse.php?rennID=<?= $r['RennID'] ?>">Ergebnisse anzeigen</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<!-- ── Ergebnisauswertung ── -->
<?php if ($rennEdit): ?>
<hr>
<h2>
    Ergebnisse: <?= htmlspecialchars($rennEdit['Startort']) ?>
    (<?= htmlspecialchars($rennEdit['Datum']) ?>)
</h2>

<?php if (empty($ergebnisse)): ?>
    <p>Keine Fahrer für dieses Rennen angemeldet.</p>
<?php else: ?>
    <?php
    $mitErgebnis = array_filter($ergebnisse, fn($e) => $e['Platzierung'] !== null);
    $ohneErgebnis = array_filter($ergebnisse, fn($e) => $e['Platzierung'] === null);
    ?>

    <h3>Wertung</h3>
    <?php if (empty($mitErgebnis)): ?>
        <p>Noch keine Ergebnisse erfasst.</p>
    <?php else: ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>Platz</th>
                <th>Fahrer</th>
                <th>Team</th>
                <th>Startnummer</th>
                <th>Fahrzeit (Sek.)</th>
            </tr>
            <?php foreach ($mitErgebnis as $e): ?>
            <tr>
                <td><strong><?= htmlspecialchars($e['Platzierung']) ?>.</strong></td>
                <td><?= htmlspecialchars($e['FahrerName']) ?></td>
                <td><?= htmlspecialchars($e['TeamName']) ?></td>
                <td><?= htmlspecialchars($e['Startnummer']) ?></td>
                <td><?= htmlspecialchars($e['gefahreneZeit']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php if (!empty($ohneErgebnis)): ?>
    <h3>Ohne Ergebnis</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Startnummer</th>
            <th>Fahrer</th>
            <th>Team</th>
        </tr>
        <?php foreach ($ohneErgebnis as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['Startnummer']) ?></td>
            <td><?= htmlspecialchars($e['FahrerName']) ?></td>
            <td><?= htmlspecialchars($e['TeamName']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

<?php endif; ?>
<?php endif; ?>

</body>
</html>
