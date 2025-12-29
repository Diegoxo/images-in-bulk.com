<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get input
$data = json_decode(file_get_contents('php://input'), true);
$prompt = $data['prompt'] ?? '';
$model = $data['model'] ?? 'dall-e-3';
$resolution = $data['resolution'] ?? '1024x1024';
$quality = $data['quality'] ?? 'standard';
$style = $data['style'] ?? 'vivid';
$format = $data['format'] ?? 'png';

if (empty($prompt)) {
    echo json_encode(['success' => false, 'error' => 'Prompt is required']);
    exit;
}

// In a real app, we check the user session and credits here
// For now, let's assume it's authorized.

$apiKey = OPENAI_API_KEY;

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo json_encode(['success' => false, 'error' => 'Configura la API Key de OpenAI en includes/config.php']);
    exit;
}

// OpenAI API Endpoint
$url = 'https://api.openai.com/v1/images/generations';

// Prepare headers
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
];

// PHP Puro: Using CURL
$ch = curl_init($url);

// Resolution mapping logic (Centralized Backend)
$mappedResolution = '1024x1024'; // Default fallback

$resolutions = [
    'dall-e-3' => [
        '1:1' => '1024x1024',
        '16:9' => '1792x1024',
        '9:16' => '1024x1792'
    ],
    'default' => [
        '1:1' => '1024x1024',
        '16:9' => '1024x576',
        '9:16' => '576x1024'
    ]
];

$modelKey = ($model === 'dall-e-3') ? 'dall-e-3' : 'default';
if (isset($resolutions[$modelKey][$resolution])) {
    $mappedResolution = $resolutions[$modelKey][$resolution];
}

// Prepare payload
$payload = [
    'model' => $model,
    'prompt' => $prompt,
    'n' => 1,
    'size' => $mappedResolution
];

// Handle model-specific parameters based on implementacion.js logic
if (strpos($model, 'dall-e') !== false) {
    $payload['response_format'] = 'url';
    if ($model === 'dall-e-3') {
        $payload['quality'] = 'standard'; // Forced to standard per implementation logic
        $payload['style'] = $style;
    }
} else if (strpos($model, 'gpt-image') !== false) {
    // GPT Image models follow different parameter names
    $payload['output_format'] = $format;
    $payload['quality'] = 'medium'; // 'medium' is equivalent to standard for these models
    // GPT models might not support response_format='url' explicitly in some versions, 
    // or return b64_json by default. We'll handle the response dynamically.
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP compatibility, though not ideal for production

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['success' => false, 'error' => 'CURL Error: ' . $error]);
    exit;
}

$response = json_decode($result, true);

if ($httpCode !== 200) {
    $errorMsg = $response['error']['message'] ?? 'API Error ' . $httpCode;
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Return the image data to the frontend
if (isset($response['data'][0]['url'])) {
    echo json_encode([
        'success' => true,
        'image_url' => $response['data'][0]['url']
    ]);
} else if (isset($response['data'][0]['b64_json'])) {
    // If it's base64, we can return it as a data URI
    $b64 = $response['data'][0]['b64_json'];
    $mime = ($format === 'jpg' || $format === 'jpeg') ? 'image/jpeg' : 'image/png';
    echo json_encode([
        'success' => true,
        'image_url' => "data:$mime;base64,$b64"
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se recibió una imagen válida de la API']);
}
