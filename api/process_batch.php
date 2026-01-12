<?php
/**
 * Batch Process Engine (The "Brain")
 * Orchestrates the generation of multiple images and streams results.
 */
require_once '../includes/config.php';
require_once '../includes/utils/subscription_helper.php';

// Disable time limit for bulk processing
set_time_limit(0);
// Prevent session locking during long requests
session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Critical for LiteSpeed/Nginx streaming

/**
 * Send a message to the client
 */
function sendEvent($data, $event = 'message')
{
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// 1. Get and Validate Input
require_once '../includes/utils/validation_helper.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!$data)
    $data = $_POST;

// Parsing: handle both array and raw string (new behavior)
$inputPrompts = is_array($data['prompts'] ?? null) ? $data['prompts'] : explode("\n", (string) ($data['prompts'] ?? ''));
$inputFilenames = is_array($data['filenames'] ?? null) ? $data['filenames'] : explode("\n", (string) ($data['filenames'] ?? ''));

$prompts = array_values(array_filter(array_map('trim', $inputPrompts)));
$filenames = array_values(array_filter(array_map('trim', $inputFilenames)));

if (empty($prompts)) {
    sendEvent(['success' => false, 'error' => 'No prompts provided'], 'error');
    exit;
}

// Delegate validation to helper
$modelCheck = ValidationHelper::validateModel($data['model'] ?? 'dall-e-3');
$resCheck = ValidationHelper::validateResolution($data['resolution'] ?? '1:1');
$formatCheck = ValidationHelper::validateFormat($data['format'] ?? 'png');

if (!$modelCheck['success'] || !$resCheck['success'] || !$formatCheck['success']) {
    $error = $modelCheck['error'] ?? ($resCheck['error'] ?? $formatCheck['error']);
    sendEvent(['success' => false, 'error' => $error], 'error');
    exit;
}

$model = $modelCheck['data'];
$resolution = $resCheck['data'];
$format = $formatCheck['data'];
$customStyle = htmlspecialchars(strip_tags($data['custom_style'] ?? ''));

// 2. Auth Check
session_start();
$userId = $_SESSION['user_id'] ?? null;
$access = validateBatchAccess($userId, $model, $resolution, count($prompts));

if (!$access['success']) {
    sendEvent(['success' => false, 'error' => $access['error']], 'error');
    exit;
}

$isPro = $access['data']['isPro'];
$subStatus = $access['data'];
session_write_close();

// 4. OpenAI Setup
$apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
if (empty($apiKey)) {
    error_log("OpenAI API Key not configured.");
    sendEvent(['success' => false, 'error' => 'Generation service unavailable.'], 'error');
    exit;
}
$apiUrl = 'https://api.openai.com/v1/images/generations';

// Resolution mapping
$resMap = [
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
$mKey = (strpos($model, 'dall-e-3') !== false) ? 'dall-e-3' : 'default';
$mappedRes = $resMap[$mKey][$resolution] ?? '1024x1024';

// 5. THE BRAIN: The Loop
$total = count($prompts);
sendEvent(['total' => $total], 'start');

for ($i = 0; $i < $total; $i++) {
    if (connection_aborted())
        break;

    $originalPrompt = $prompts[$i];
    $fullPrompt = !empty($customStyle) ? "$originalPrompt. $customStyle" : $originalPrompt;
    $currentName = !empty($filenames[$i]) ? $filenames[$i] . '.' . $format : "image_" . ($i + 1) . "." . $format;

    try {
        $payload = [
            'model' => $model,
            'prompt' => $fullPrompt,
            'n' => 1,
            'size' => $mappedRes
        ];

        if (strpos($model, 'dall-e') !== false) {
            $payload['response_format'] = 'url';
            if ($model === 'dall-e-3') {
                $payload['quality'] = 'standard';
                $payload['style'] = 'vivid';
            }
        } else {
            // High-level logic for GPT Image models
            $payload['quality'] = 'medium';
            $payload['output_format'] = $format;
        }

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($result, true);

        if ($httpCode === 200) {
            $base64 = '';

            if (isset($response['data'][0]['url'])) {
                // Handle URL response (DALL-E style)
                $imageUrl = $response['data'][0]['url'];
                $imgData = @file_get_contents($imageUrl);
                if ($imgData) {
                    $base64 = 'data:image/' . $format . ';base64,' . base64_encode($imgData);
                }
            } else if (isset($response['data'][0]['b64_json'])) {
                // Handle Base64 response (GPT style)
                $mime = ($format === 'jpg' || $format === 'jpeg') ? 'image/jpeg' : 'image/png';
                $base64 = "data:$mime;base64," . $response['data'][0]['b64_json'];
            }

            if (!empty($base64)) {
                if ($userId) {
                    $db = getDB();
                    $stmt = $db->prepare("INSERT INTO generations (user_id, prompt, image_url, model, resolution) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $originalPrompt, 'streamed_bulk', $model, $mappedRes]);

                    // DEDUCT CREDITS FOR PRO USERS
                    if ($isPro) {
                        $cost = calculateImageCost($model, $resolution);
                        deductCredits($userId, $cost);
                    }
                }

                sendEvent([
                    'index' => $i,
                    'success' => true,
                    'image' => $base64,
                    'fileName' => $currentName,
                    'prompt' => $originalPrompt
                ], 'generation');
            } else {
                throw new Exception("Encountered empty image data from API");
            }
        } else {
            $errorMsg = $response['error']['message'] ?? 'OpenAI API Error (Code ' . $httpCode . ')';

            // Check if it's a safety/policy error
            if (strpos(strtolower($errorMsg), 'safety') !== false || strpos(strtolower($errorMsg), 'policy') !== false) {
                // Pass specific safety error to user
                throw new Exception($errorMsg);
            }

            // Log real error internally
            error_log("OpenAI API Fail: " . $errorMsg);
            // Throw generic for everything else
            throw new Exception("Provider temporarily unavailable (Code $httpCode)");
        }

    } catch (Exception $e) {
        $msg = $e->getMessage();
        // Allow safety messages to pass through specific check above
        if (strpos(strtolower($msg), 'safety') !== false || strpos(strtolower($msg), 'allowed') !== false) {
            sendEvent(['index' => $i, 'success' => false, 'error' => $msg], 'generation');
        } else {
            // Log detailed error for admin
            error_log("Generation Error: " . $msg);
            // Send generic safe message to user
            sendEvent(['index' => $i, 'success' => false, 'error' => 'Generation failed due to a processing error.'], 'generation');
        }
    }

    usleep(100000);
}

sendEvent(['completed' => true], 'done');
