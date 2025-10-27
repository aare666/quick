<?php
$visitor_id = isset($_GET['visitor_id']) ? $_GET['visitor_id'] : '';

if (empty($visitor_id)) {
    die("Invalid visitor ID.");
}

$redirect_file = "redirects/$visitor_id.txt";

if (file_exists($redirect_file)) {
    $url = trim(file_get_contents($redirect_file));

    // Optional: Ensure it's a valid relative or full URL
    if (!empty($url)) {
        header("Location: $url");
        exit;
    } else {
        die("Redirect URL is empty.");
    }
} else {
    die("No redirect found for this visitor.");
}
?>
