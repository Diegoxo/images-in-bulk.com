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

$payload = [
    'model' => $model,
    'prompt' => $prompt,
    'n' => 1,
    'size' => $resolution,
    'response_format' => 'url'
];

// Handle model-specific parameters
if (strpos($model, 'dall-e') !== false) {
    if ($model === 'dall-e-3') {
        $payload['quality'] = $quality;
        $payload['style'] = $style;
    }
} else {
    // GPT Image models support output_format, quality, etc.
    $payload['output_format'] = $format;
    $payload['quality'] = $quality;
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

// Return the temporary URL to the frontend
echo json_encode([
    'success' => true,
    'image_url' => $response['data'][0]['url']
]);
