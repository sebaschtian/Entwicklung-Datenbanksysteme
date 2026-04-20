<!-- Autor: Sebastian Rieg
	 Fahrer Rennanmeldung-->
	 
<?php
	include '../includes/db.inc.php';
    include '../includes/fahrer.inc.php';
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
    </form>