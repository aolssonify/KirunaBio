<?php
include 'session_manager.php';  // Inkludera session_manager för hantering av sessioner
include 'sql.php';  // Inkludera sql.php för att ansluta till databasen

// Starta sessionen om den inte redan är startad
if (!isset($_SESSION)) {
    session_start(); 
}

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['KundID'])) {
    header("Location: login.html");  // Skicka användaren till inloggningssidan om denne inte är inloggad
    exit;  // Avbryt skriptexekveringen
}

// Variabel för att hålla meddelandet om adressuppdatering
$statusMessage = '';

// Ta bort bokningar som är äldre än 6 timmar efter visningstiden
$cleanupQuery = "DELETE FROM bokning 
                 WHERE TIMESTAMPDIFF(HOUR, CONCAT(CURDATE(), ' ', tid), NOW()) > 6"; 
$cleanupStmt = $pdo->prepare($cleanupQuery);  // Förbered frågan
$cleanupStmt->execute();  

// Hämta användarens information (förnamn och efternamn) samt adress från Adress-tabellen
$KundID = $_SESSION['KundID'];  // Hämta användarens kundID från sessionen
$query = "
    SELECT kund.förnamn, kund.efternamn, adress.AdressID, adress.Gata, adress.Postnummer, adress.Stad, adress.Land
    FROM kund
    LEFT JOIN adress ON kund.KundID = adress.KundID
    WHERE kund.KundID = :kundID";  // Hämta kundens förnamn, efternamn och adressinformation
$stmt = $pdo->prepare($query);  // Förbered frågan
$stmt->bindParam(':kundID', $KundID, PDO::PARAM_INT);  // Koppla kundID till frågan
$stmt->execute(); 
$user = $stmt->fetch();  // Hämta resultatet

// Om användaren skickar in formuläret för att uppdatera sin adress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gata'], $_POST['postnummer'], $_POST['stad'], $_POST['land'])) {
    $newGata = $_POST['gata']; 
    $newPostnummer = $_POST['postnummer'];  // Nytt postnummer från formuläret
    $newStad = $_POST['stad']; 
    $newLand = $_POST['land'];  // Nytt land från formuläret

    // Kolla om adressen redan finns för användaren
    if (isset($user['AdressID'])) {  // Kontrollera om AdressID är satt
        // Uppdatera befintlig adress
        $updateQuery = "
            UPDATE adress 
            SET Gata = :gata, Postnummer = :postnummer, Stad = :stad, Land = :land 
            WHERE AdressID = :adressID AND KundID = :kundID";  // Uppdatera den befintliga adressen för kunden
        $updateStmt = $pdo->prepare($updateQuery);  // Förbered uppdateringsfrågan
        $updateStmt->bindParam(':gata', $newGata, PDO::PARAM_STR);  
        $updateStmt->bindParam(':postnummer', $newPostnummer, PDO::PARAM_STR); 
        $updateStmt->bindParam(':stad', $newStad, PDO::PARAM_STR); 
        $updateStmt->bindParam(':land', $newLand, PDO::PARAM_STR); 
        $updateStmt->bindParam(':adressID', $user['AdressID'], PDO::PARAM_INT);
        $updateStmt->bindParam(':kundID', $KundID, PDO::PARAM_INT);
        if ($updateStmt->execute()) {
            $statusMessage = "Adressen har uppdaterats!";
        } else {
            $statusMessage = "Misslyckades att uppdatera adressen.";
        }
    } else {
        // Lägg till en ny adress för användaren
        $insertQuery = "
            INSERT INTO adress (KundID, Gata, Postnummer, Stad, Land) 
            VALUES (:kundID, :gata, :postnummer, :stad, :land)";  // Lägg till en ny adress om ingen adress finns
        $insertStmt = $pdo->prepare($insertQuery);  // Förbered frågan
        $insertStmt->bindParam(':kundID', $KundID, PDO::PARAM_INT);  // Koppla kundID
        $insertStmt->bindParam(':gata', $newGata, PDO::PARAM_STR);  
        $insertStmt->bindParam(':postnummer', $newPostnummer, PDO::PARAM_STR);
        $insertStmt->bindParam(':stad', $newStad, PDO::PARAM_STR);  
        $insertStmt->bindParam(':land', $newLand, PDO::PARAM_STR); 
        if ($insertStmt->execute()) {
            $statusMessage = "Ny adress har lagts till!";
        } else {
            $statusMessage = "Misslyckades att lägga till ny adress.";
        }
    }
}

// Hämta användarens bokningsstatistik
$bookingQuery = "SELECT film.Titel, bokning.bokningstid, bokning.tid FROM bokning 
                 INNER JOIN film ON bokning.FilmID = film.FilmID 
                 WHERE bokning.KundID = :kundID";  // Hämta bokningar som kunden har gjort
$bookingStmt = $pdo->prepare($bookingQuery);  // Förbered frågan
$bookingStmt->bindParam(':kundID', $KundID, PDO::PARAM_INT);  
$bookingStmt->execute();  // Kör frågan
$bookings = $bookingStmt->fetchAll();  // Hämta alla bokningar
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">  <!-- Inkludera CSS-filen -->
</head>
<body>

    <header>
        <div class="user-info">
        <?php echo displaySessionInfo(); ?>  <!-- Visa användarens sessioninformation -->
        </div>
        <h1>User Dashboard</h1>  <!-- Sidans huvudrubrik -->
        <nav>
            <ul>
                <li><a href="index.php">Hem</a></li>  <!-- Länk till hemsidan -->
                <li><a href="products.php">Film/bokning</a></li>  <!-- Länk till bokningssidan -->
                <li><a href="logout.php">Logga ut</a></li>  <!-- Länk för att logga ut -->
            </ul>
        </nav>
        <div class="logo-container">
            <img src="images/Biograf logga.png" alt="Kiruna Biograf Logotyp" class="logo">  <!-- Visa logotypen -->
        </div>
    </header>

    <main class="main-container">

        <!-- Sektion för att visa bokningar -->
        <div class="box-info">
            <h2>Mina Bokningar</h2>
            <div class="stats-container">
                <?php if ($bookings): ?>  <!-- Kontrollera om bokningar finns -->
                    <?php foreach ($bookings as $booking): ?>  <!-- Gå igenom varje bokning och visa den -->
                        <div class="stat-card">
                            <p><strong>Film:</strong> <?php echo htmlspecialchars($booking['Titel']); ?></p>
                            <p><strong>Bokningstid:</strong> <?php echo htmlspecialchars($booking['bokningstid']); ?></p>
                            <p><strong>Visningstid:</strong> <?php echo htmlspecialchars($booking['tid']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Du har inga bokningar.</p>  <!-- Meddelande om inga bokningar finns -->
                <?php endif; ?>
            </div>
        </div>

        <!-- Sektion för att uppdatera adress -->
        <div class="box-stats">
            <h2>Uppdatera Din Adress</h2>
            
            <!-- Visa statusmeddelande här, inne i adressrutan -->
            <?php if ($statusMessage): ?>  <!-- Kontrollera om det finns ett meddelande att visa -->
                <div class="status-message">
                    <?php echo htmlspecialchars($statusMessage); ?>  <!-- Visa statusmeddelandet -->
                </div>
            <?php endif; ?>

            <form action="user_dashboard.php" method="POST">  <!-- Formulär för att uppdatera adress -->
                <label for="gata">Gata:</label>
                <input type="text" id="gata" name="gata" value="<?php echo isset($user['Gata']) ? htmlspecialchars($user['Gata']) : ''; ?>" required>  <!-- Fält för att ange ny gata -->

                <label for="postnummer">Postnummer:</label>
                <input type="text" id="postnummer" name="postnummer" value="<?php echo isset($user['Postnummer']) ? htmlspecialchars($user['Postnummer']) : ''; ?>" required>  <!-- Fält för att ange nytt postnummer -->

                <label for="stad">Stad:</label>
                <input type="text" id="stad" name="stad" value="<?php echo isset($user['Stad']) ? htmlspecialchars($user['Stad']) : ''; ?>" required>  <!-- Fält för att ange ny stad -->

                <label for="land">Land:</label>
                <input type="text" id="land" name="land" value="<?php echo isset($user['Land']) ? htmlspecialchars($user['Land']) : ''; ?>" required>  <!-- Fält för att ange nytt land -->

                <button type="submit">Uppdatera Adress</button>  <!-- Knapp för att skicka formuläret -->
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Min Bio. Alla rättigheter förbehållna.</p> 
    </footer>

</body>
</html>
