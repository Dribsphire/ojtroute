<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Workplace Map - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin_style.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

    <style>
        #map {
            height: calc(100vh - 100px);
            /* Adjust based on your header/nav */
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        main {
            padding: 20px;
            /* Assume admin_nav takes up some width or height, handled by admin_style.css */
        }

        h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'admin_nav.php'; ?>
    <main>
        <h2>Workplace Map</h2>
        <div id="map"></div>
    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        // Initialize the map centered at Bacolod, Negros Occidental
        var map = L.map('map').setView([10.67730, 122.94900], 11.39);

        // Add regular OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
    </script>
</body>

</html>