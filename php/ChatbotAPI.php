<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mech Spec LMS - SpechBot Gemini Backend
|--------------------------------------------------------------------------
|
| The browser NEVER receives the Gemini API key.
| The browser sends a question here.
| PHP loads the private API key from secrets.php.
| PHP communicates with Gemini and returns the answer.
|
*/

header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', '0');
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| Load private configuration
|--------------------------------------------------------------------------
*/

$secretsFile = __DIR__ . '/secrets.php';

if (is_file($secretsFile)) {
    require_once $secretsFile;
}

$apiKey = defined('GEMINI_API_KEY')
    ? trim((string) GEMINI_API_KEY)
    : '';



/*
|--------------------------------------------------------------------------
| Read request
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');

$input = json_decode(
    $rawInput ?: '{}',
    true
);

$userQuery = trim(
    (string) ($input['message'] ?? '')
);

if ($userQuery === '') {
    echo json_encode([
        'status' => 'error',
        'response' => 'Please enter a question.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Basic message length protection
|--------------------------------------------------------------------------
*/

if (mb_strlen($userQuery) > 2000) {
    echo json_encode([
        'status' => 'error',
        'response' => 'Your message is too long. Please keep it under 2,000 characters.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Gemini configuration check
|--------------------------------------------------------------------------
*/

if ($apiKey === '') {

    echo json_encode([
        'status' => 'error',
        'response' => 'Gemini AI is not configured yet. Please check php/secrets.php.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| SpechBot system context
|--------------------------------------------------------------------------
*/

$systemContext = <<<PROMPT
You are SpechBot, the AI assistant for Mech Spec LMS.

Mech Spec LMS is a secure Learning Management System.

Your responsibilities:
- Help users understand the LMS.
- Explain courses and learning features.
- Explain registration and login.
- Explain course enrolment and purchasing.
- Explain certificates.
- Explain basic platform security features.
- Help users navigate the platform.

Keep responses:
- Professional
- Friendly
- Clear
- Concise
- Written in English

Do not use emojis unless the user specifically asks for them.

Do not claim that a feature exists if it is not part of Mech Spec LMS.

Known platform features include:
- Student accounts
- Instructor accounts
- Administrator accounts
- Course browsing
- Course creation
- Course enrolment
- Course progress
- Certificates
- Role-Based Access Control
- Password hashing
- Login rate limiting
- Email verification
- Password reset
- Google authentication
- AI assistance

PROMPT;


/*
|--------------------------------------------------------------------------
| Gemini request
|--------------------------------------------------------------------------
*/

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                [
                    'text' => $systemContext .
                        "\n\nUser question:\n" .
                        $userQuery
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 700
    ]
];


/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/

$modelsToTry = [
    'gemini-2.5-flash',
    'gemini-2.0-flash',
    'gemini-flash-latest'
];


$lastError = '';
$httpCode = 0;


/*
|--------------------------------------------------------------------------
| Send request
|--------------------------------------------------------------------------
*/

foreach ($modelsToTry as $modelName) {

    $url =
        'https://generativelanguage.googleapis.com/v1beta/models/' .
        rawurlencode($modelName) .
        ':generateContent?key=' .
        rawurlencode($apiKey);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
        ),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,

        /*
         * Keep SSL verification enabled.
         * Do NOT disable certificate verification.
         */
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);

    $httpCode = (int) curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);

    curl_close($ch);


    /*
    |--------------------------------------------------------------------------
    | Successful response
    |--------------------------------------------------------------------------
    */

    if ($response !== false && $httpCode === 200) {

        $result = json_decode(
            $response,
            true
        );

        $aiText =
            $result['candidates'][0]['content']['parts'][0]['text']
            ?? '';

        if ($aiText !== '') {

            /*
             * Escape HTML before displaying Gemini output.
             */
            $safeText = htmlspecialchars(
                $aiText,
                ENT_QUOTES,
                'UTF-8'
            );

            /*
             * Basic markdown formatting.
             */
            $safeText = preg_replace(
                '/\*\*(.*?)\*\*/s',
                '<strong>$1</strong>',
                $safeText
            );

            $safeText = preg_replace(
                '/`([^`]+)`/',
                '<code>$1</code>',
                $safeText
            );

            $safeText = nl2br($safeText);

            echo json_encode([
                'status' => 'success',
                'source' => 'gemini',
                'response' => $safeText
            ]);

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Error handling
    |--------------------------------------------------------------------------
    */

    if ($response !== false) {

        $errorData = json_decode(
            $response,
            true
        );

        $lastError =
            $errorData['error']['message']
            ?? 'Gemini returned an unexpected response.';

    } elseif ($curlError !== '') {

        $lastError = $curlError;
    }
}


/*
|--------------------------------------------------------------------------
| Final error
|--------------------------------------------------------------------------
*/

echo json_encode([
    'status' => 'error',
    'httpCode' => $httpCode,
    'response' =>
        'Gemini AI could not process the request right now. ' .
        'Please try again.',
    'debug' => $lastError
]);