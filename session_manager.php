<?php
// Starta sessionen om den inte redan är startad
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sätt inloggningstiden för sessionen om den inte redan är satt
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();  // Spara tidpunkten då användaren loggade in
}

// Kontrollera om användaren är inloggad med KundID
function isLoggedIn() {
    return isset($_SESSION['KundID']);  // Kontrollera om KundID är satt i sessionen
}

// Kontrollera om användaren är admin
function isAdmin() {
    return isset($_SESSION['Roll']) && $_SESSION['Roll'] === 'admin';  // Kontrollera om användaren har admin-roll
}

// Visa länk för "Logga in", "Logga ut" och kontrollera adminstatus
function loginLogoutLink() {
    if (isLoggedIn()) {
        // Visa "Administratör" om användaren är admin, annars "Mitt Konto"
        if (isAdmin()) {
            return '<li><a href="admin_dashboard.php">Administratör</a></li>
                    <li><a href="logout.php">Logga ut</a></li>';
        } else {
            return '<li><a href="user_dashboard.php">Mitt Konto</a></li>
                    <li><a href="logout.php">Logga ut</a></li>';
        }
    } else {
        return '<li><a href="login.html">Logga in</a></li>
                <li><a href="register.html">Registrera dig</a></li>';
    }
}

// Visa sessionsinformation endast om det är en vanlig webbsida (ej AJAX)
function displaySessionInfo() {
    // Kontrollera om förfrågan är en AJAX-förfrågan
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        if (isset($_SESSION['KundID']) && isset($_SESSION['förnamn'])) {
            $förnamn = $_SESSION['förnamn'];  // Förnamn sparas i sessionen
            $loginTime = isset($_SESSION['login_time']) ? $_SESSION['login_time'] : 0;

            // Returnera inloggningsstatus och JavaScript för att visa sessionens varaktighet
            return "
                <div class='login-status'>Du är inloggad som $förnamn.</div>
                <div id='session-duration'>Laddar inloggningstid...</div>
                <script>
                    function updateSessionDuration() {
                        const loginTime = " . $loginTime . ";
                        const currentTime = Math.floor(Date.now() / 1000);
                        let duration = currentTime - loginTime;

                        const hours = Math.floor(duration / 3600);
                        const minutes = Math.floor((duration % 3600) / 60);
                        const seconds = duration % 60;

                        document.getElementById('session-duration').textContent = hours + ' timmar, ' + minutes + ' minuter, ' + seconds + ' sekunder';
                    }

                    // Uppdatera varaktigheten var 1 sekund
                    setInterval(updateSessionDuration, 1000);
                    updateSessionDuration();
                </script>";
        } else {
            return "<div class='login-status'>Du är inte inloggad.</div>";
        }
    }

    // Om det är en AJAX-förfrågan, returnera inget HTML
    return '';
}
?>
