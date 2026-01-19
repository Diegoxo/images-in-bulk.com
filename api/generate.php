<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// 1. Get and Validate Input
require_once '../includes/utils/validation_helper.php';
$data = json_decode(file_get_contents('php://input'), true);

$prompt = htmlspecialchars(strip_tags(trim($data['prompt'] ?? '')));
if (empty($prompt)) {
    echo json_encode(['success' => false, 'error' => 'Prompt is required']);
    exit;
}

// Delegate validation to helper
$modelCheck = ValidationHelper::validateModel($data['model'] ?? 'dall-e-3');
$resCheck = ValidationHelper::validateResolution($data['resolution'] ?? '1:1');
$formatCheck = ValidationHelper::validateFormat($data['format'] ?? 'png');

if (!$modelCheck['success'] || !$resCheck['success'] || !$formatCheck['success']) {
    $error = $modelCheck['error'] ?? ($resCheck['error'] ?? $formatCheck['error']);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

$model = $modelCheck['data'];
$resolution = $resCheck['data'];
$format = $formatCheck['data'];
$quality = $data['quality'] ?? 'standard';
$style = htmlspecialchars(strip_tags($data['style'] ?? 'vivid'));

// 2. Auth & Limit Checks
require_once '../includes/utils/subscription_helper.php';

session_start();
$userId = $_SESSION['user_id'] ?? null;
$access = validateUserAccess($userId, $model, $resolution);

if (!$access['success']) {
    echo json_encode(['success' => false, 'error' => $access['error']]);
    exit;
}

$isPro = $access['data']['isPro'];
$subStatus = $access['data'];
session_write_close();

$apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

if (empty($apiKey)) {
    error_log("OpenAI API Key not configured.");
    echo json_encode(['success' => false, 'error' => 'The generation service is currently unavailable.']);
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

// Prepare payload with common parameters
$payload = [
    'model' => $model,
    'prompt' => $prompt,
    'n' => 1,
    'size' => $mappedResolution
];

// Handle model-specific parameters strictly
if (strpos($model, 'dall-e') !== false) {
    // DALL-E specific logic
    $payload['response_format'] = 'url';
    if ($model === 'dall-e-3') {
        $payload['quality'] = 'standard';
        $payload['style'] = $style;
    }
} else {
    // GPT Image models specific logic
    $payload['quality'] = 'medium';
    $payload['output_format'] = $format;
    // CRITICAL: Ensure response_format is NEVER here for GPT models
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    error_log("CURL Error in generate.php: " . $error);
    echo json_encode(['success' => false, 'error' => 'Connection error. Please try again later.']);
    exit;
}

$response = json_decode($result, true);

if ($httpCode !== 200) {
    $errorMsg = $response['error']['message'] ?? 'API Error ' . $httpCode;

    // Check if it's a safety/policy error
    if (strpos(strtolower($errorMsg), 'safety') !== false || strpos(strtolower($errorMsg), 'policy') !== false) {
        // Pass specific safety error to user
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    } else {
        // Log real error internally
        error_log("OpenAI API Fail in generate.php: " . $errorMsg);
        // Return generic error
        echo json_encode(['success' => false, 'error' => 'Generation failed due to a processing error.']);
    }
    exit;
}

// Return the image data to the frontend
if (isset($response['data'][0]['url'])) {
    $imageUrl = $response['data'][0]['url'];

    // Log generation in database (only if user is logged in)
    if (isset($_SESSION['user_id'])) {
        try {
            $db = getDB();
            $userId = $_SESSION['user_id'];

            // Insert into generations table
            $stmt = $db->prepare("INSERT INTO generations (user_id, prompt, image_url, model, resolution) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $prompt, $imageUrl, $model, $mappedResolution]);

            // DEDUCT CREDITS FOR PRO USERS & Increment cycle counter
            if ($isPro) {
                $cost = calculateImageCost($model, $resolution);
                deductCredits($userId, $cost);
            }
            // Increment cycle counter for everyone (active/cancelled)
            $db->prepare("UPDATE subscriptions SET images_in_period = images_in_period + 1 WHERE user_id = ? AND status IN ('active', 'cancelled')")->execute([$userId]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Database logging error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl
    ]);
} else if (isset($response['data'][0]['b64_json'])) {
    // If it's base64, we can return it as a data URI
    $b64 = $response['data'][0]['b64_json'];
    $mime = ($format === 'jpg' || $format === 'jpeg') ? 'image/jpeg' : 'image/png';
    $imageUrl = "data:$mime;base64,$b64";

    // Log generation in database (only if user is logged in)
    if (isset($_SESSION['user_id'])) {
        try {
            $db = getDB();
            $userId = $_SESSION['user_id'];

            // Insert into generations table
            $stmt = $db->prepare("INSERT INTO generations (user_id, prompt, image_url, model, resolution) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $prompt, 'base64_image', $model, $mappedResolution]);

            // DEDUCT CREDITS FOR PRO USERS & Increment cycle counter
            if ($isPro) {
                $cost = calculateImageCost($model, $resolution);
                deductCredits($userId, $cost);
            }
            // Increment cycle counter for everyone (active/cancelled)
            $db->prepare("UPDATE subscriptions SET images_in_period = images_in_period + 1 WHERE user_id = ? AND status IN ('active', 'cancelled')")->execute([$userId]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Database logging error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se recibió una imagen válida de la API']);
}
