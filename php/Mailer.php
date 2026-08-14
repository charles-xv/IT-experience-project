<?php
// Real authenticated SMTP mailer for Mech Spec LMS.
// Gmail SMTP: smtp.gmail.com, port 587 + STARTTLS (default) or 465 + SSL.
// Use a Google App Password for SMTP_PASSWORD; never use the normal Gmail password.

function app_secret(string $name, string $default = ''): string
{
    static $loaded = false;
    if (!$loaded) {
        $secretFile = __DIR__ . '/secrets.php';
        if (is_file($secretFile)) {
            require_once $secretFile;
        }
        $loaded = true;
    }

    if (defined($name)) {
        return (string) constant($name);
    }

    $env = getenv($name);
    return $env !== false ? (string) $env : $default;
}

function smtp_read($socket, int $expectedClass = 2): void
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] !== '-') {
            break;
        }
    }
    $code = (int) substr(trim($response), 0, 3);
    if ($code < ($expectedClass * 100) || $code >= (($expectedClass + 1) * 100)) {
        throw new RuntimeException('SMTP server returned ' . $code . ': ' . trim($response));
    }
}

function smtp_command($socket, string $command, int $expectedClass = 2): void
{
    fwrite($socket, $command . "\r\n");
    smtp_read($socket, $expectedClass);
}

function smtp_send_message(
    string $host,
    int $port,
    string $encryption,
    string $username,
    string $password,
    string $from,
    string $fromName,
    string $to,
    string $subject,
    string $html,
    string $text
): void {
    $remote = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        $remote . ':' . $port,
        $errno,
        $errstr,
        12,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if (!$socket) {
        throw new RuntimeException('SMTP connection failed: ' . $errstr);
    }
    stream_set_timeout($socket, 12);

    try {
        smtp_read($socket, 2);
        $helo = gethostname() ?: 'localhost';
        smtp_command($socket, 'EHLO ' . $helo, 2);

        if ($encryption === 'tls') {
            smtp_command($socket, 'STARTTLS', 2);
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto !== true) {
                throw new RuntimeException('Could not establish SMTP TLS encryption.');
            }
            smtp_command($socket, 'EHLO ' . $helo, 2);
        }

        smtp_command($socket, 'AUTH LOGIN', 3);
        smtp_command($socket, base64_encode($username), 3);
        smtp_command($socket, base64_encode($password), 2);
        smtp_command($socket, 'MAIL FROM:<' . $from . '>', 2);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', 2);
        smtp_command($socket, 'DATA', 3);

        $safeFromName = str_replace(["\r", "\n"], '', $fromName);
        $safeSubject = str_replace(["\r", "\n"], '', $subject);
        $boundary = '=_MechSpec_' . bin2hex(random_bytes(12));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($safeSubject) . '?=';
        $encodedName = '=?UTF-8?B?' . base64_encode($safeFromName) . '?=';

        $body = 'From: ' . $encodedName . ' <' . $from . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: ' . $encodedSubject . "\r\n"
            . "Date: " . date(DATE_RFC2822) . "\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $helo . ">\r\n"
            . "MIME-Version: 1.0\r\n"
            . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n"
            . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . str_replace(["\r\n", "\r", "\n"], "\r\n", $text) . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . str_replace(["\r\n", "\r", "\n"], "\r\n", $html) . "\r\n"
            . '--' . $boundary . "--\r\n";

        // SMTP requires lines beginning with a dot to be dot-stuffed.
        $body = preg_replace('/^\./m', '..', $body);
        fwrite($socket, $body . "\r\n.\r\n");
        smtp_read($socket, 2);
        smtp_command($socket, 'QUIT', 2);
    } finally {
        fclose($socket);
    }
}

function send_html_email(string $to, string $subject, string $html, string $text): bool
{
    $host = app_secret('SMTP_HOST', 'smtp.gmail.com');
    $port = (int) app_secret('SMTP_PORT', '587');
    $encryption = strtolower(app_secret('SMTP_ENCRYPTION', 'tls'));
    $username = app_secret('SMTP_USERNAME', '');
    $password = app_secret('SMTP_PASSWORD', '');
    $from = app_secret('MAIL_FROM_EMAIL', $username);
    $name = app_secret('MAIL_FROM_NAME', 'Mech Spec LMS');

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('SMTP mail rejected: invalid sender or recipient address.');
        return false;
    }
    if ($username === '' || $password === '') {
        error_log('SMTP mail not configured: SMTP_USERNAME/SMTP_PASSWORD missing.');
        return false;
    }
    if (!in_array($encryption, ['tls', 'ssl'], true)) {
        error_log('SMTP mail not configured: SMTP_ENCRYPTION must be tls or ssl.');
        return false;
    }

    try {
        smtp_send_message($host, $port, $encryption, $username, $password, $from, $name, $to, $subject, $html, $text);
        return true;
    } catch (Throwable $e) {
        error_log('SMTP send failed: ' . $e->getMessage());
        return false;
    }
}

function app_url(string $path = ''): string
{
    $base = rtrim(app_secret('APP_URL', ''), '/');
    if ($base !== '') {
        return $base . '/' . ltrim($path, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    return $scheme . '://' . $host . $scriptDir . '/' . ltrim($path, '/');
}
