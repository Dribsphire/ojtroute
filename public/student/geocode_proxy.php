<?php
/**
 * Geocoding Proxy for Location Search
 * This proxy handles requests to Nominatim API to avoid CORS issues
 * and provides proper headers for mobile compatibility
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get search query from request
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query is required']);
    exit;
}

// Validate query length
if (strlen($query) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query too short']);
    exit;
}

// Build Nominatim API URL
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$limit = min(max($limit, 1), 10); // Limit between 1 and 10

$countryCode = isset($_GET['countrycodes']) ? $_GET['countrycodes'] : 'ph';

$apiUrl = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'format' => 'json',
    'q' => $query,
    'limit' => $limit,
    'countrycodes' => $countryCode,
    'addressdetails' => 1
]);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'OJTRoute-System/1.0 (Student Workplace Locator)',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Accept-Language: en'
    ]
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Handle errors
if ($response === false) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch location data',
        'details' => $error
    ]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'Geocoding service returned an error',
        'http_code' => $httpCode
    ]);
    exit;
}

// Decode and validate response
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from geocoding service'
    ]);
    exit;
}

// Return the results
echo json_encode($data);
