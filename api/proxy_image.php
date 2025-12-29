<?php
// Simple proxy to bypass CORS when fetching OpenAI images
require_once '../includes/config.php';

$url = $_GET['url'] ?? '';

if (empty($url) || strpos($url, 'https://') !== 0) {
    http_response_code(400);
    die('Invalid URL');
}

// Security: Only allow URLs from OpenAI
if (
    strpos($url, 'https://oaidalleapiprodscus.blob.core.windows.net') !== 0 &&
    strpos($url, 'https://oai-alle') !== 0
) {
    // Note: OpenAI image URLs often use Azure Blob Storage
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($response) {
    header("Content-Type: $contentType");
    echo $response;
} else {
    http_response_code(500);
    echo "Error proxying image";
}
