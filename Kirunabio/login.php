<?php
// Inkludera sql.php för att få tillgång till PDO-anslutningen
include 'sql.php';

// Sätt content-typen till HTML och utf-8 teckenkodning
header('Content-type: text/html; charset=utf-8');

// Starta sessionen
session_start();

// Kontrollera om formuläret har skickats via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kontrollera om e-post och lösenord finns i POST-variablerna
    if (isset($_POST['email']) && isset($_POST['password'])) {
        // Hämta och sanera inmatade data
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Validera e-postadressen
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['login_error'] = 'Ogiltig e-postadress.';
            header("Location: login.php");
            exit;
        }

        // Kontrollera om användaren finns i databasen med e-post
        $query = "SELECT * FROM kund WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Kontrollera om en användare med den angivna e-posten hittades
        if ($row = $stmt->fetch()) {
            // Kontrollera att kolumnen 'Lösenord' finns i resultatet
            if (isset($row['Lösenord'])) {
                // Verifiera lösenordet
                if (password_verify($password, $row['Lösenord'])) {
                    // Generera ett nytt session-ID för säkerhet
                    session_regenerate_id(true);

                    // Spara användarinformation i sessionen
                    $_SESSION['KundID'] = $row['KundID'];
                    $_SESSION['förnamn'] = $row['Förnamn'];
                    $_SESSION['Roll'] = $row['Roll']; // Spara användarens roll

                    // Omdirigera alla användare till index.php oavsett roll
                    header("Location: index.php");
                    exit; // Avsluta för att säkerställa att ingen annan kod körs efter omdirigeringen
                } else {
                    $_SESSION['login_error'] = 'Felaktigt lösenord. Försök igen.';
                    header("Location: login.php");
                    exit;
                }
            } else {
                $_SESSION['login_error'] = "Kolumnen 'Lösenord' hittades inte i databasen.";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['login_error'] = 'Inget konto hittades med den e-postadressen.';
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = 'Vänligen fyll i både e-post och lösenord.';
        header("Location: login.php");
        exit;
    }
}
?>
