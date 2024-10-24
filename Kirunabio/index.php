<?php
include 'session_manager.php';
?>

<!DOCTYPE html>
<html lang="sv">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Min Bio - Startsida</title>
    <!-- Länk till extern CSS för styling -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
    <div class="user-info">
        <?php echo displaySessionInfo(); ?>
        </div>
        <h1>Hem</h1>
        <!-- Navigation med länkar till produkter och registrering -->
        <nav>
        <ul>
            <li><a href="products.php">Produkter</a></li>
             <!-- Funktionen för att visa antingen "Logga in" eller "Logga ut" -->
             <?php echo loginLogoutLink(); ?>
        </ul>
        </nav>
        <!-- Logotyp för Kiruna Biograf -->
        <div class="logo-container">
            <img src="images/Biograf logga.png" alt="Kiruna Biograf Logotyp" class="logo">
        </div>
    </header>

    <div class="main-content" style="display: flex 1;">

        <!-- Snacksmeny Section -->
        <section id="snacks">
            <h2>Snacksmeny</h2>
            <p>Här kan du hitta vårt stora urval av snacks och drycker!</p>
            <ul>
                <?php
                // Anslut till databasen för att hämta snacks
                $host = 'localhost';
                $db = 'andre';
                $user = 'root';
                $pass = '';
                $charset = 'utf8mb4';
                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $pdo = new PDO($dsn, $user, $pass);

                // Hämta snacks från databasen
                $querySnacks = "SELECT Produkt, Pris, Bild FROM produkt";
                $stmtSnacks = $pdo->prepare($querySnacks);
                $stmtSnacks->execute();
                $snacks = $stmtSnacks->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (!empty($snacks)): ?>
                    <!-- Loopar igenom alla snacks och visar dem -->
                    <?php foreach ($snacks as $snack): ?>
                        <li>
                            <?php echo $snack['Produkt']; ?> - <?php echo $snack['Pris']; ?> kr
                            <img src="<?php echo $snack['Bild']; ?>" alt="<?php echo $snack['Produkt']; ?>">
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Meddelande om inga snacks finns tillgängliga -->
                    <p>Inga snacks tillgängliga för tillfället.</p>
                <?php endif; ?>
            </ul>
        </section>
        
        <aside style="flex: 1;">
        <!-- Sektion för att visa de senaste filmerna -->
            <div id="movies">
                <h2>I Blickfånget</h2>
                <div class="movie-list">
                    <?php
                    // Hämta de tre senaste filmerna från databasen med den uppdaterade strukturen
                    $queryMovies = "SELECT Titel, Genre, Regissör, Längd, Kommer, Beskrivning, Bild FROM film ORDER BY FilmID DESC LIMIT 3";
                    $stmtMovies = $pdo->prepare($queryMovies);
                    $stmtMovies->execute();
                    $movies = $stmtMovies->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php foreach ($movies as $movie): ?>
                        <div class="movie">
                            <!-- Visar filmens bild och titel -->
                            <img src="<?php echo htmlspecialchars($movie['Bild']); ?>" alt="<?php echo htmlspecialchars($movie['Titel']); ?>">
                            <h3><?php echo htmlspecialchars($movie['Titel']); ?></h3>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

    <!-- Sidfot med upphovsrätt -->
    <footer>
        <p>&copy; 2024 Min Bio. Alla rättigheter förbehållna.</p>
    </footer>

</body>
</html>
