<?php
include 'session_manager.php';
include 'sql.php';

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['KundID'])) {
    echo "Inloggning krävs";
    exit;
}

// Hämta söksträngen
$q = $_GET['q'] ?? '';

if (empty($q)) {
    echo "Ingen söksträng mottagen";
    exit;
}

// Kör SQL-frågan
$stmt = $pdo->prepare("SELECT FilmID, Titel, Längd, Beskrivning, Bild FROM film WHERE Titel LIKE ?");
$stmt->execute(["%$q%"]);
$filmer = $stmt->fetchAll();

// Om inga filmer hittas
if (empty($filmer)) {
    echo "<p>Inga filmer hittades.</p>";
} else {
    foreach ($filmer as $film) {
        echo '<div class="film-card">';
        echo '<img src="' . htmlspecialchars($film['Bild']) . '" alt="' . htmlspecialchars($film['Titel']) . '" class="aside-img">';
        echo '<h3>' . htmlspecialchars($film['Titel']) . '</h3>';
        echo '<p><strong>Längd:</strong> ' . htmlspecialchars($film['Längd']) . ' minuter</p>';
        echo '<div class="film-description"><p>' . htmlspecialchars($film['Beskrivning']) . '</p></div>';
        echo '</div>';
    }
}
?>
