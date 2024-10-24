<?php
include 'sql.php';  // Se till att databasen ansluts korrekt

header('Content-Type: application/json');
session_start(); // Starta sessionen för att använda sessionvariabler

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['film_id']) && isset($_POST['tid'])) {
    $filmId = $_POST['film_id'];
    $tid = $_POST['tid']; // Tidpunkt 15:00 eller 21:00
    
    // Kontrollera om användaren är inloggad
    if (!isset($_SESSION['KundID'])) {
        echo json_encode(['error' => 'Du måste vara inloggad för att boka.']);
        exit;
    }

    $KundID = $_SESSION['KundID']; // Hämta inloggad användares kundID

    // Kontrollera om filmen existerar
    $query = "SELECT Bokningar FROM film WHERE FilmID = :film_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['film_id' => $filmId]);
    $film = $stmt->fetch();
    
    if ($film) {
        // Uppdatera bokningsantalet i film-tabellen
        $newBookings = $film['Bokningar'] + 1;
        $updateQuery = "UPDATE film SET Bokningar = :Bokningar WHERE FilmID = :film_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute(['Bokningar' => $newBookings, 'film_id' => $filmId]);

        // Spara bokningen i bokning-tabellen med vald tid
        $bookingQuery = "INSERT INTO bokning (FilmID, KundID, bokningstid, tid) VALUES (:film_id, :kund_id, NOW(), :tid)";
        $bookingStmt = $pdo->prepare($bookingQuery);
        $bookingStmt->bindParam(':film_id', $filmId, PDO::PARAM_INT);
        $bookingStmt->bindParam(':kund_id', $KundID, PDO::PARAM_INT);
        $bookingStmt->bindParam(':tid', $tid, PDO::PARAM_STR);

        if ($bookingStmt->execute()) {
            // Returnera JSON-data med bokningsmeddelande och visningstid
            echo json_encode(['message' => 'Bokning lyckades!', 'tid' => $tid]);
        } else {
            echo json_encode(['error' => 'Bokningen misslyckades']);
        }
    } else {
        echo json_encode(['error' => 'Film hittades inte']);
    }
} else {
    echo json_encode(['error' => 'Ogiltig förfrågan']);
}
