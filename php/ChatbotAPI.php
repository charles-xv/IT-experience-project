<?php
// php/ChatbotAPI.php - Secure Backend Proxy for Gemini AI API
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable PHP display_errors output from corrupting JSON responses
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Get raw JSON input
$input = json_decode(file_get_contents('php://input'), true);
$userQuery = trim($input['message'] ?? '');

if (empty($userQuery)) {
    echo json_encode(['status' => 'error', 'response' => 'Message is empty.']);
    exit;
}

// System prompt to instruct Gemini about Mech Spec LMS
$systemContext = "You are SpechBot, the AI Virtual Assistant for Mech Spec LMS, a secure Learning Management System developed by Mech Spec Technologies. " .
                 "Keep your answers concise, professional, friendly, and helpful. " .
                 "Platform features: Courses (Ethical Hacking, Web Security, JavaScript, HTML), " .
                 "Pricing (Free courses + Paid courses with lifetime access, no subscriptions), " .
                 "Security (bcrypt password hashing, rate limiting, RBAC, 30-min session timeout), " .
                 "Certificates (automatically awarded on 100% completion).";

// Load the API key securely from the local secrets file (never committed to Git)
$secretsFile = __DIR__ . '/secrets.php';
$apiKey = '';

if (file_exists($secretsFile)) {
    require_once $secretsFile;
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
}

// Also check environment variable as a fallback (for production servers)
if (empty($apiKey)) {
    $apiKey = getenv('GEMINI_API_KEY') ?: '';
}

if (empty($apiKey)) {
    echo json_encode([
        'status' => 'success',
        'source' => 'fallback',
        'response' => "🤖 <em>[API Simulation Mode]</em>: To enable live Gemini responses, configure your API key in <code>php/secrets.php</code>."
    ]);
    exit;
}

// Prepare payload for Gemini API
$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $systemContext . "\n\nUser Question: " . $userQuery]
            ]
        ]
    ]
];

// Active Gemini model names supported by the API (fastest first)
$modelsToTry = [
    'gemini-flash-latest',
    'gemini-2.0-flash',
    'gemini-2.5-flash'
];

$response = null;
$httpCode = 0;
$lastError = '';

foreach ($modelsToTry as $modelName) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent?key=" . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    // Ignore SSL certificate verification on local XAMPP Windows environment
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!empty($aiText)) {
            $formattedText = nl2br(htmlspecialchars($aiText));
            $formattedText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formattedText);
            $formattedText = preg_replace('/`(.*?)`/', '<code>$1</code>', $formattedText);

            echo json_encode([
                'status' => 'success',
                'source' => 'gemini_api (' . $modelName . ')',
                'response' => $formattedText
            ]);
            exit;
        }
    }

    if ($response) {
        $errData = json_decode($response, true);
        if (isset($errData['error']['message'])) {
            $lastError = $errData['error']['message'];
        }
    } elseif (!empty($curlErr)) {
        $lastError = "cURL error: " . $curlErr;
    }
}

// Fallback — return actual API error for debugging
echo json_encode([
    'status'   => 'error_debug',
    'httpCode' => $httpCode,
    'apiError' => $lastError,
    'response' => "⚠️ <strong>API Error (HTTP $httpCode)</strong>" .
                  ($lastError ? " : <em>$lastError</em>" : " — Empty response or model not found.")
]);
