<?php
include 'session_manager.php';
include 'sql.php';

// Hämta nuvarande filmer från databasen (inklusive antal bokningar)
$today = date('Y-m-d');
$query = "SELECT FilmID, Titel, Längd, Beskrivning, Bild, Bokningar FROM film WHERE Kommer <= :today OR Kommer IS NULL";
$stmt = $pdo->prepare($query);
$stmt->execute(['today' => $today]);
$filmer = $stmt->fetchAll();

// Hämta kommande filmer från databasen
$queryKommande = "SELECT FilmID, Titel, Längd, Beskrivning, Bild FROM film WHERE Kommer > :today";
$stmtKommande = $pdo->prepare($queryKommande);
$stmtKommande->execute(['today' => $today]);
$kommandeFilmer = $stmtKommande->fetchAll();
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Våra Filmer</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <div class="user-info">
            <?php echo displaySessionInfo(); ?>
        </div>
        <h1>Produkter</h1>
        <nav>
            <ul>
                <li><a href="index.php">Hem</a></li>
                <?php echo loginLogoutLink(); ?>
                <li>
                    <a href="#" id="search-link" onclick="activateSearch()">Sök</a>
                    <input type="text" id="search-input" placeholder="Sök film..." onkeyup="searchMovies(this.value)" style="display:none;">
                </li>
            </ul>
        </nav>
        <div style="display: flex; justify-content: space-between;">
            <div class="logo-container">
                <img src="images/Biograf logga.png" alt="Kiruna Biograf Logotyp" class="logo">
            </div>
        </div>
    </header>

    <h2>Nuvarande filmer</h2>

    <div class="main-content" style="display: flex;">
        <section class="film-section" style="flex: 7;">
            <div id="film-grid" class="film-grid-container">
                <?php if ($filmer): ?>
                    <!-- Loopar igenom alla nuvarande filmer -->
                    <?php foreach ($filmer as $film): ?>
                        <div class="film-card">
                            <img src="<?php echo $film['Bild']; ?>" alt="<?php echo $film['Titel']; ?>" class="aside-img">
                            <h3><?php echo $film['Titel']; ?></h3>
                            <p><strong>Längd:</strong> <?php echo $film['Längd']; ?> minuter</p>
                            <div class="film-description">
                                <p><?php echo $film['Beskrivning']; ?></p>
                            </div>

                            <!-- Bokningsformulär -->
                            <form class="booking-form">
                                <input type="hidden" name="film_id" value="<?php echo $film['FilmID']; ?>">

                                <!-- Välj tid -->
                                <label for="tid-<?php echo $film['FilmID']; ?>">Välj tid:</label>
                                <select name="tid" id="tid-<?php echo $film['FilmID']; ?>" required>
                                    <option value="15:00">15:00</option>
                                    <option value="21:00">21:00</option>
                                </select>
                                <div class="boka-button">
                                    <!-- Boka-knapp -->
                                    <button type="submit">Boka</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Inga filmer tillgängliga just nu.</p>
                <?php endif; ?>
            </div>
        </section>

        <aside class="kom-container" style="flex: 3;">
            <h2>Kommande Filmer</h2>
            <div class="kommande-filmer">
                <?php if ($kommandeFilmer): ?>
                    <!-- Loopar igenom alla kommande filmer -->
                    <?php foreach ($kommandeFilmer as $film): ?>
                    <div class="film">
                        <img src="<?php echo $film['Bild']; ?>" alt="<?php echo $film['Titel']; ?>" class="aside-img">
                        <h3><?php echo $film['Titel']; ?></h3>
                        <p><strong>Längd:</strong> <?php echo $film['Längd']; ?> minuter</p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Inga kommande filmer tillgängliga just nu.</p>
                <?php endif; ?>
            </div>
        </aside>
    </div>

    <footer>
        <p>&copy; 2024 Min Bio</p>
    </footer>

    <script>
        // Hantera formulärens bokningsskick
        $(document).on('submit', '.booking-form', function(e) {
            e.preventDefault(); // Förhindra att sidan laddas om

            var form = $(this);
            var filmId = form.find('input[name="film_id"]').val();
            var tid = form.find('select[name="tid"]').val();

            // Skicka bokningsdata till book_movie.php
            $.ajax({
                url: 'book_movie.php',
                type: 'POST',
                data: {
                    film_id: filmId,
                    tid: tid
                },
                success: function(response) {
                    var data = JSON.parse(response); // Konvertera svaret till JSON

                    if (data.message) {
                        // Om bokningen lyckades
                        alert("Bokning lyckades! Tid: " + data.tid);
                    } else if (data.error) {
                        // Om bokningen misslyckades
                        alert("Fel: " + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert("Ett fel uppstod: " + error);
                }
            });
        });

        // Funktion för att söka filmer med AJAX
        function searchMovies(query) {
            if (query.length === 0) {
                location.reload(); // Ladda om sidan om sökfältet är tomt
                return;
            }

            $.get('search_film.php', { q: query })
                .done(function(data) {
                    $('#film-grid').html(data); // Uppdatera film-grid med sökresultat
                })
                .fail(function(xhr, status, error) {
                    console.error("AJAX-anrop misslyckades:", status, error);
                    console.error("Svar från servern:", xhr.responseText);
                });
        }

        // Funktion för att aktivera sökrutan
        function activateSearch() {
            console.log("Sök-länk klickad");  // Kontrollera om klicket registreras
            var searchLink = document.getElementById("search-link");
            var searchInput = document.getElementById("search-input");

            // Dölj länken och visa sökrutan
            searchLink.style.display = "none";  // Dölj länken
            searchInput.style.display = "inline-block";  // Visa sökrutan
            searchInput.focus();  // Sätt fokus på sökrutan
        }
    </script>

</body>
</html>
