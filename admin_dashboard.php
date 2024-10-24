<?php
include 'session_manager.php';  // Inkludera filen som hanterar sessioner
include 'sql.php';  // Inkludera filen som hanterar databaskoppling

// Kontrollera om användaren är inloggad som admin
if (!isset($_SESSION['Roll']) || $_SESSION['Roll'] !== 'admin') {
    // Om användaren inte är admin, omdirigera till inloggningssidan
    header("Location: login.html");
    exit;  // Avsluta skriptet efter omdirigering
}

// Variabel för att hålla meddelanden till användaren (exempelvis bekräftelse eller felmeddelanden)
$statusMessage = "";

// Kontrollera om det är en POST-begäran (när formulär skickas in)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hantera tillägg eller uppdatering av film
    if (isset($_POST['add_or_update'])) {
        // Hämta data från formuläret och sätt standardvärden om de är tomma
        $titel = $_POST['titel'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $regissor = $_POST['regissor'] ?? '';
        $langd = $_POST['langd'] ?? '';
        $kommer = $_POST['kommer'] ?? '';
        $beskrivning = $_POST['beskrivning'] ?? '';

        // Om en film redan existerar (vi har ett filmID), redigera den
        if (isset($_POST['film_id']) && !empty($_POST['film_id'])) {
            // Förbered en SQL-fråga för att uppdatera filmen baserat på filmID
            $filmId = $_POST['film_id'];
            $stmt = $pdo->prepare("UPDATE film SET Titel=?, Genre=?, Regissör=?, Längd=?, Kommer=?, Beskrivning=? WHERE FilmID=?");
            $stmt->execute([$titel, $genre, $regissor, $langd, $kommer, $beskrivning, $filmId]);  // Kör SQL-frågan med värden från formuläret
            $statusMessage = "<p>Film uppdaterad!</p>";  // Uppdatering lyckades, visa meddelande
        } else {
            // Om det inte finns något filmID, lägg till en ny film i databasen
            if ($titel && $genre && $regissor && $langd && $kommer && $beskrivning) {
                // Kontrollera om en bild har laddats upp
                if (isset($_FILES['bild']) && $_FILES['bild']['error'] === 0) {
                    // Hantera filnamn och filuppladdning
                    $bildFil = str_replace(' ', '_', basename($_FILES['bild']['name']));  // Ta bort mellanslag från filnamnet
                    $bildPath = '/images/' . $bildFil;  // Sökväg där bilden ska sparas
                    $bildFullPath = __DIR__ . '/images/' . $bildFil;  // Fullständig sökväg på servern
                    move_uploaded_file($_FILES['bild']['tmp_name'], $bildFullPath);  // Flytta den uppladdade filen till rätt plats
                    
                    // Förbered en SQL-fråga för att infoga den nya filmen i databasen
                    $stmt = $pdo->prepare("INSERT INTO film (Titel, Genre, Regissör, Längd, Kommer, Beskrivning, Bild) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titel, $genre, $regissor, $langd, $kommer, $beskrivning, $bildPath]);  // Kör SQL-frågan med värden från formuläret
                    $statusMessage = "<p>Film tillagd!</p>";  // Filmtillägg lyckades, visa meddelande
                }
            } else {
                // Om obligatoriska fält saknas, visa ett felmeddelande
                $statusMessage = "<p>Vänligen fyll i alla obligatoriska fält.</p>";
            }
        }
    }

    // Hantera borttagning av film
    if (isset($_POST['delete_film_id'])) {
        $filmId = $_POST['delete_film_id'];  // Hämta filmID från formuläret för borttagning
        $stmt = $pdo->prepare("DELETE FROM film WHERE FilmID = ?");  // Förbered en SQL-fråga för att ta bort filmen
        $stmt->execute([$filmId]);  // Kör frågan
        $statusMessage = "<p>Film borttagen!</p>";  // Borttagning lyckades, visa meddelande
    }
}

// Kontrollera om en film är vald för redigering
$filmToEdit = null;
if (isset($_POST['edit']) && isset($_POST['film_id'])) {
    $filmId = $_POST['film_id'];  // Hämta filmID från formuläret
    $stmt = $pdo->prepare("SELECT * FROM film WHERE FilmID = ?");  // Förbered en SQL-fråga för att hämta filmdata
    $stmt->execute([$filmId]);  // Kör SQL-frågan
    $filmToEdit = $stmt->fetch();  // Hämta filmdata från databasen för redigering
}

// Hämta alla filmer från databasen för att visa i filmlistan
$filmQuery = "SELECT * FROM film";  // Förbered en SQL-fråga för att hämta alla filmer
$filmStmt = $pdo->query($filmQuery);  // Kör frågan
$filmer = $filmStmt->fetchAll();  // Hämta resultatet som en array

// Hämta statistik över bokningar per film
$filmStatQuery = "
    SELECT film.Titel, COUNT(bokning.BokningID) AS Bokningar
    FROM film
    LEFT JOIN bokning ON film.FilmID = bokning.FilmID
    GROUP BY film.FilmID";  // Hämta antal bokningar för varje film
$filmStatStmt = $pdo->query($filmStatQuery);  // Kör SQL-frågan
$filmStats = $filmStatStmt->fetchAll();  // Hämta resultatet

// Hämta kundspecifik statistik, exkludera administratörer
$kundStatistikQuery = "
    SELECT 
        kund.KundID, 
        CONCAT(kund.Förnamn, ' ', kund.Efternamn) AS namn, 
        kund.Email, 
        COUNT(bokning.BokningID) AS total_bokningar, 
        MAX(bokning.bokningstid) AS senaste_besök
    FROM 
        kund
    LEFT JOIN 
        bokning ON kund.KundID = bokning.KundID
    WHERE 
        kund.Roll != 'admin'  -- Exkludera admin från statistiken
    GROUP BY 
        kund.KundID
    ORDER BY 
        total_bokningar DESC";  // Hämta kundstatistik, sorterat efter flest bokningar
$kundStatistikStmt = $pdo->prepare($kundStatistikQuery);  // Förbered SQL-frågan
$kundStatistikStmt->execute();  // Kör SQL-frågan
$kundStatistik = $kundStatistikStmt->fetchAll();  // Hämta resultatet
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Statistik och Filmhantering</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // Bekräfta om användaren vill ta bort en film
        function confirmDelete() {
            return confirm("Är du säker på att du vill ta bort denna film?");
        }
    </script>
</head>
<body>

<header>
    <div class="user-info">
        <?php echo displaySessionInfo(); ?>  <!-- Visa information om inloggad användare -->
    </div>
    <h1>Admin Panel</h1>
    <nav>
        <ul>
            <li><a href="products.php">Filmer</a></li>
            <li><a href="admin_dashboard.php?tab=crud">Filmhantering</a></li>  <!-- Länk till CRUD-sektionen -->
            <?php echo loginLogoutLink(); ?>  <!-- Visa inloggning eller utloggning beroende på användarens session -->
        </ul>
    </nav>
    <div class="logo-container">
        <img src="images/Biograf logga.png" alt="Kiruna Biograf Logotyp" class="logo">  <!-- Logotyp för webbplatsen -->
    </div>
</header>

<main class="main-container">
    <div class="content-container">

        <?php if (isset($_GET['tab']) && $_GET['tab'] == 'crud'): ?>
            <!-- Filmhanteringssektionen (CRUD) -->
            <div class="box-info">
                <h2><?php echo isset($filmToEdit) ? 'Redigera film' : 'Lägg till ny film'; ?></h2>  <!-- Visa om det är redigering eller ny film -->
                <?php if ($statusMessage): ?>
                    <div class="status-message">
                        <?php echo $statusMessage; ?>  <!-- Visa statusmeddelande -->
                    </div>
                <?php endif; ?>
                <form action="admin_dashboard.php?tab=crud" method="POST" enctype="multipart/form-data">  <!-- Formulär för att lägga till eller redigera film -->
                    <input type="hidden" name="film_id" value="<?php echo isset($filmToEdit) ? $filmToEdit['FilmID'] : ''; ?>">  <!-- Dold input för filmID -->

                    <label for="titel">Titel:</label>
                    <input type="text" id="titel" name="titel" value="<?php echo isset($filmToEdit) ? htmlspecialchars($filmToEdit['Titel']) : ''; ?>" required>  <!-- Titel för filmen -->

                    <label for="genre">Genre:</label>
                    <input type="text" id="genre" name="genre" value="<?php echo isset($filmToEdit) ? htmlspecialchars($filmToEdit['Genre']) : ''; ?>" required>  <!-- Genre -->

                    <label for="regissor">Regissör:</label>
                    <input type="text" id="regissor" name="regissor" value="<?php echo isset($filmToEdit) ? htmlspecialchars($filmToEdit['Regissör']) : ''; ?>" required>  <!-- Regissör -->

                    <label for="langd">Längd (minuter):</label>
                    <input type="text" id="langd" name="langd" value="<?php echo isset($filmToEdit) ? htmlspecialchars($filmToEdit['Längd']) : ''; ?>" required>  <!-- Längd -->

                    <label for="kommer">Kommer (datum):</label>
                    <input type="date" id="kommer" name="kommer" value="<?php echo isset($filmToEdit) ? htmlspecialchars($filmToEdit['Kommer']) : ''; ?>" required>  <!-- Datum då filmen kommer -->

                    <label for="beskrivning">Beskrivning:</label>
                    <textarea id="beskrivning" name="beskrivning" required><?php echo isset($filmToEdit) ? htmlspecialchars($filmToEdit['Beskrivning']) : ''; ?></textarea>  <!-- Beskrivning av filmen -->

                    <label for="bild">Omslagsbild:</label>
                    <input type="file" id="bild" name="bild" <?php echo isset($filmToEdit) ? '' : 'required'; ?>>  <!-- Fält för att ladda upp omslagsbild -->

                    <button type="submit" name="add_or_update"><?php echo isset($filmToEdit) ? 'Uppdatera Film' : 'Lägg till Film'; ?></button>  <!-- Skicka-knapp -->
                </form>
            </div>

            <!-- Lista över filmer med alternativ för att redigera och ta bort -->
            <div class="box-stats">
                <h2>Filmlista</h2>
                <div class="film-grid">
                <?php foreach ($filmer as $film): ?>
                    <div class="film-card-admin">
                        <p><strong>Titel:</strong> <?php echo htmlspecialchars($film['Titel']); ?></p>
                        <p><strong>Genre:</strong> <?php echo htmlspecialchars($film['Genre']); ?></p>
                        <p><strong>Längd:</strong> <?php echo htmlspecialchars($film['Längd']); ?> minuter</p>
                        <p><strong>Kommer:</strong> <?php echo htmlspecialchars($film['Kommer']); ?></p>
                        <p><strong>Beskrivning:</strong> <?php echo htmlspecialchars(substr($film['Beskrivning'], 0, 50)); ?>...</p>

                        <div>
                            <!-- Formulär för att redigera film -->
                            <form action="admin_dashboard.php?tab=crud" method="POST" style="display:inline-block;">
                                <input type="hidden" name="film_id" value="<?php echo $film['FilmID']; ?>">
                                <button type="submit" name="edit">Redigera</button>
                            </form>

                            <!-- Formulär för att ta bort film -->
                            <form action="admin_dashboard.php?tab=crud" method="POST" style="display:inline-block;" onsubmit="return confirmDelete();">
                                <input type="hidden" name="delete_film_id" value="<?php echo $film['FilmID']; ?>">
                                <button type="submit" name="delete">Ta bort</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Statistiksektionen -->
            <div class="box-stats">
                <h2>Bokningsstatistik per film</h2>
                <table>
                    <tr>
                        <th>Filmtitel</th>
                        <th>Antal Bokningar</th>
                    </tr>
                    <?php foreach ($filmStats as $film): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($film['Titel']); ?></td>
                        <td><?php echo htmlspecialchars($film['Bokningar']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Kundspecifik statistiksektion -->
            <div class="box-stats">
                <h2>Kund-statistik</h2>
                <?php foreach ($kundStatistik as $kund): ?>
                    <div class="stat-card">
                        <p><strong>KundID:</strong> <?php echo htmlspecialchars($kund['KundID']); ?></p>
                        <p><strong>Namn:</strong> <?php echo htmlspecialchars($kund['namn']); ?></p>
                        <p><strong>E-post:</strong> <?php echo htmlspecialchars($kund['Email']); ?></p>
                        <p><strong>Antal Bokningar:</strong> <?php echo htmlspecialchars($kund['total_bokningar']); ?></p>
                        <p><strong>Senaste Besök:</strong> <?php echo htmlspecialchars($kund['senaste_besök']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2024 Min Bio. Alla rättigheter förbehållna.</p>
</footer>

</body>
</html>
