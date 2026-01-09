<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get input
// Get input
$data = json_decode(file_get_contents('php://input'), true);
$rawPrompt = $data['prompt'] ?? '';
$prompt = htmlspecialchars(strip_tags(trim($rawPrompt)));

$rawModel = $data['model'] ?? 'dall-e-3';
$model = preg_replace('/[^a-z0-9\-\.]/', '', $rawModel);

// WHITELIST CHECK
$allowed_models = ['dall-e-3', 'gpt-image-1.5', 'gpt-image-1-mini'];
if (!in_array($model, $allowed_models)) {
    echo json_encode(['success' => false, 'error' => 'Invalid model identifier.']);
    exit;
}

$resolution = $data['resolution'] ?? '1:1';
$quality = $data['quality'] ?? 'standard';
$style = htmlspecialchars(strip_tags($data['style'] ?? 'vivid'));
$format = $data['format'] ?? 'png';

if (empty($prompt)) {
    echo json_encode(['success' => false, 'error' => 'Prompt is required']);
    exit;
}

// Check user subscription and model access
$isPro = false;
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT plan_type, status FROM subscriptions WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $sub = $stmt->fetch();
        if ($sub && $sub['plan_type'] === 'pro') {
            $isPro = true;
        }
    } catch (Exception $e) {
        error_log("Sub check error: " . $e->getMessage());
    }
}

// Restriction: Only Pro users can use GPT Image models
if (!$isPro && $model !== 'dall-e-3') {
    echo json_encode(['success' => false, 'error' => 'This model is only available for PRO users.']);
    exit;
}

// Restriction: Only Pro users can use non-square resolutions
if (!$isPro && $resolution !== '1:1') {
    echo json_encode(['success' => false, 'error' => 'Custom resolutions (16:9, 9:16) are only available for PRO users.']);
    exit;
}

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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP compatibility, though not ideal for production

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

            // Update usage_log counter
            $currentMonth = date('Y-m');
            $stmtCheck = $db->prepare("SELECT id, images_count FROM usage_log WHERE user_id = ? AND month_year = ?");
            $stmtCheck->execute([$userId, $currentMonth]);
            $usageRow = $stmtCheck->fetch();

            if ($usageRow) {
                // Update existing record
                $newCount = $usageRow['images_count'] + 1;
                $stmtUpdate = $db->prepare("UPDATE usage_log SET images_count = ? WHERE id = ?");
                $stmtUpdate->execute([$newCount, $usageRow['id']]);
            } else {
                // Create new record for this month
                $stmtInsert = $db->prepare("INSERT INTO usage_log (user_id, images_count, month_year) VALUES (?, 1, ?)");
                $stmtInsert->execute([$userId, $currentMonth]);
            }
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

            // Update usage_log counter
            $currentMonth = date('Y-m');
            $stmtCheck = $db->prepare("SELECT id, images_count FROM usage_log WHERE user_id = ? AND month_year = ?");
            $stmtCheck->execute([$userId, $currentMonth]);
            $usageRow = $stmtCheck->fetch();

            if ($usageRow) {
                // Update existing record
                $newCount = $usageRow['images_count'] + 1;
                $stmtUpdate = $db->prepare("UPDATE usage_log SET images_count = ? WHERE id = ?");
                $stmtUpdate->execute([$newCount, $usageRow['id']]);
            } else {
                // Create new record for this month
                $stmtInsert = $db->prepare("INSERT INTO usage_log (user_id, images_count, month_year) VALUES (?, 1, ?)");
                $stmtInsert->execute([$userId, $currentMonth]);
            }
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
