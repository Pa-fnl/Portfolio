<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévisions Météo - Zeus</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Arrière-plan animé -->
    <div class="background-container"></div>

    <header class="header">
        <div class="zeus-container">
            <img src="images/Zeus.jpg" alt="Zeus" class="zeus-image">
            <h1 class="title">Les Prévisions Météo de Zeus</h1>
        </div>
    </header>

    <main class="container">
        <div class="forecast">
            <?php
            // Clé API et coordonnées géographiques
            $apiKey = "d9956a3b9a3c1f769e44290cf67d1af2";
            $lat = "44.60954984533323";
            $lon = "-1.1197549342861468";
            $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$apiKey&units=metric&lang=fr";

            // Récupération des données
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            // Validation de la réponse
            if ($data['cod'] == "200") {
                echo "<h2 class='forecast-title'>Prévisions météo pour " . $data['city']['name'] . " :</h2>";

                // Affichage des prévisions (limitées ici à 5 éléments pour simplifier)
                foreach ($data['list'] as $index => $forecast) {
                    if ($index % 8 == 0) { // Une prévision par jour
                        $date = date("d/m/Y", strtotime($forecast['dt_txt']));
                        $temp = $forecast['main']['temp'];
                        $description = $forecast['weather'][0]['description'];
                        $iconCode = $forecast['weather'][0]['icon'];
                        $iconUrl = "https://openweathermap.org/img/wn/{$iconCode}@2x.png";

                        echo "<div class='forecast-item'>";
                        echo "<h3>$date</h3>";
                        echo "<img src='{$iconUrl}' alt='{$description}' class='weather-icon'>";
                        echo "<p>Température : <strong>{$temp}°C</strong></p>";
                        echo "<p>Description : <strong>{$description}</strong></p>";
                        echo "</div>";
                    }
                }
            } else {
                echo "<p class='error-message'>Erreur : impossible de récupérer les données météo.</p>";
            }
            ?>
        </div>
    </main>

    <footer class="footer">
        <p>Les prévisions divines par Zeus © 2025</p>
    </footer>

    <script src="script.js"></script>
</body>

</html>
