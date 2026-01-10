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
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$rawPrompts = $data['prompts'] ?? [];
$prompts = array_map(function ($p) {
    return htmlspecialchars(strip_tags(trim($p)));
}, $rawPrompts);

$filenames = $data['filenames'] ?? [];
$rawModel = $data['model'] ?? 'dall-e-3';
// Strict whitelist-like regex for model
$model = preg_replace('/[^a-z0-9\-\.]/', '', $rawModel);

// WHITELIST CHECK
$allowed_models = ['dall-e-3', 'gpt-image-1.5', 'gpt-image-1-mini'];
if (!in_array($model, $allowed_models)) {
    sendEvent(['success' => false, 'error' => 'Invalid model identifier.'], 'error');
    exit;
}

$resolution = $data['resolution'] ?? '1:1';
$format = $data['format'] ?? 'png';
$customStyle = htmlspecialchars(strip_tags($data['custom_style'] ?? ''));

if (empty($prompts)) {
    sendEvent(['success' => false, 'error' => 'No prompts provided'], 'error');
    exit;
}

// 2. Auth Check
session_start();
$userId = $_SESSION['user_id'] ?? null;
$isPro = false;
$freeImagesCount = 0;
$freeLimit = 3;

if ($userId) {
    // USE HELPER FOR CONSISTENT STATUS CHECK
    $subStatus = getUserSubscriptionStatus($userId);
    $isPro = $subStatus['isPro'];
    $freeImagesCount = $subStatus['freeImagesCount'];
    $freeLimit = $subStatus['freeLimit'];
}
session_write_close();

// 3. Restriction Checks
if (!$isPro) {
    if ($model !== 'dall-e-3') {
        sendEvent(['success' => false, 'error' => 'Model restricted to PRO'], 'error');
        exit;
    }
    if ($resolution !== '1:1') {
        sendEvent(['success' => false, 'error' => 'Resolutions restricted to PRO'], 'error');
        exit;
    }

    // Check Total Usage Limits
    $requestedCount = count($prompts);
    if (($freeImagesCount + $requestedCount) > $freeLimit) {
        $remaining = max(0, $freeLimit - $freeImagesCount);
        if ($remaining === 0) {
            sendEvent(['success' => false, 'error' => 'Free limit reaced. Please upgrade.'], 'error');
        } else {
            sendEvent(['success' => false, 'error' => "Limit reached. You can only generate $remaining more images."], 'error');
        }
        exit;
    }
}

// 4. OpenAI Setup
$apiKey = OPENAI_API_KEY;
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
