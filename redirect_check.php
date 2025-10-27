<?php
// redirect_check.php

if (!isset($_COOKIE['visitor_id'])) {
    echo json_encode(['redirect' => false]);
    exit;
}

$visitor_id = $_COOKIE['visitor_id'];
$redirect_file = __DIR__ . "/redirects/$visitor_id.txt";

if (file_exists($redirect_file)) {
    $url = trim(file_get_contents($redirect_file));
    if ($url) {
        // Once read, delete redirect file so it's one-time redirect
        unlink($redirect_file);
        echo json_encode(['redirect' => true, 'url' => $url]);
        exit;
    }
}

echo json_encode(['redirect' => false]);
exit;
?>
