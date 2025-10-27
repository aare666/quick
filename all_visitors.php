<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$json_log_file = 'visitors.json';
$logs = file_exists($json_log_file) ? json_decode(file_get_contents($json_log_file), true) : [];
if (!is_array($logs)) $logs = [];

// Group logs by visitor_id
$grouped = [];
foreach ($logs as $entry) {
    $id = $entry['visitor_id'] ?? 'unknown';
    if (!isset($grouped[$id])) {
        $grouped[$id] = [];
    }
    $grouped[$id][] = $entry;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All Visitors Logs (Grouped)</title>
<style>
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #0c0c0c;
    color: #ff4c4c;
    padding: 20px;
  }
  h1 {
    text-align: center;
    margin-bottom: 20px;
  }
  button.copy-btn {
    background: #8b0000;
    color: white;
    font-weight: bold;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 20px;
  }
  button.copy-btn:hover {
    background: #ff0000;
  }
  .visitor-group {
    background: #1a0000;
    border: 1px solid #ff4c4c;
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 15px;
  }
  .visitor-id {
    font-weight: bold;
    margin-bottom: 10px;
    font-size: 18px;
  }
  .log-entry {
    background: #2a0000;
    margin-bottom: 8px;
    padding: 10px;
    border-radius: 6px;
    font-family: monospace;
    white-space: pre-wrap;
  }
  .empty-msg {
    text-align: center;
    color: #ff8c8c;
  }
</style>
</head>
<body>

<h1>All Visitors Logs (Grouped)</h1>

<?php if (empty($grouped)): ?>
  <p class="empty-msg">No visitors logged yet.</p>
<?php else: ?>
  <button class="copy-btn" onclick="copyLogs()">Copy All Logs</button>

  <?php foreach ($grouped as $visitor_id => $entries): ?>
    <div class="visitor-group">
      <div class="visitor-id">Visitor ID: <?= htmlspecialchars($visitor_id) ?></div>
      <?php foreach ($entries as $entry): ?>
        <div class="log-entry"><?= htmlspecialchars(json_encode($entry, JSON_PRETTY_PRINT)) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<script>
  function copyLogs() {
    const visitorGroups = document.querySelectorAll('.visitor-group');
    let combinedText = '';
    visitorGroups.forEach(group => {
      combinedText += group.innerText + "\n\n";
    });
    navigator.clipboard.writeText(combinedText).then(() => {
      alert('All grouped logs copied to clipboard!');
    }).catch(() => {
      alert('Failed to copy logs. Please try manually.');
    });
  }
</script>

</body>
</html>
