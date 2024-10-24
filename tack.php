<?php
include 'sql.php';

// Sätt content-typen till HTML och utf-8 teckenkodning
header('Content-type: text/html; charset=utf-8');


// Ställ in teckenkodning för anslutningen till utf8mb4
mysqli_set_charset($connection, 'utf8mb4');

// Kontrollera om formuläret har skickats via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kontrollera om alla nödvändiga POST-variabler finns
    if (isset($_POST['förnamn']) && isset($_POST['efternamn']) && isset($_POST['email']) && isset($_POST['password'])) {
        // Hämta och sanera data från formuläret för att undvika SQL-injektion
        $förnamn = mysqli_real_escape_string($connection, $_POST['förnamn']);
        $efternamn = mysqli_real_escape_string($connection, $_POST['efternamn']);
        $email = mysqli_real_escape_string($connection, $_POST['email']);
        $password = mysqli_real_escape_string($connection, $_POST['password']);

        // Kontrollera att alla fält har data (är inte tomma)
        if (empty($förnamn) || empty($efternamn) || empty($email) || empty($password)) {
            die("Alla fält måste fyllas i.");
        }

        // Hasha lösenordet innan det lagras i databasen
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Förbered SQL-frågan för att lägga till en ny kund i databasen
        $query = "INSERT INTO kund (förnamn, efternamn, email, lösenord) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);

        // Kontrollera om förberedelsen av SQL-frågan lyckades
        if (!$stmt) {
            die("Förberedelse misslyckades: " . mysqli_error($connection));
        }

        // Bind parametrar till SQL-frågan och kör den
        mysqli_stmt_bind_param($stmt, "ssss", $förnamn, $efternamn, $email, $hashed_password);
        $execute_result = mysqli_stmt_execute($stmt);

        // Kontrollera om insättningen lyckades
        if ($execute_result) {
            // Hämta det auto-genererade ID:t för den nya kunden
            $last_id = mysqli_insert_id($connection);

            // Skapa en JavaScript alert för att visa ett tack-meddelande
            echo "<script type='text/javascript'>
                    alert('Tack $förnamn $efternamn! Du är nu registrerad som en användare på vår biograf. Ditt KundID är $last_id.');
                    window.location.href = 'index.php'; // Skicka tillbaka användaren till startsidan efter alerten
                  </script>";
        } else {
            // Visa ett felmeddelande om insättningen misslyckades
            echo "<p>Det gick inte att registrera dig. Försök igen senare. Fel: " . mysqli_stmt_error($stmt) . "</p>";
        }

        // Stäng den förberedda SQL-frågan
        mysqli_stmt_close($stmt);
    } else {
        // Om något fält saknas i formuläret
        echo "<p>Vänligen fyll i alla fält i formuläret.</p>";
    }

    // Stäng databasanslutningen
    mysqli_close($connection);
} else {
    // Om sidan besöks utan att formulärdata har skickats
    echo "<p>Inget formulärdata mottaget.</p>";
}
?>
