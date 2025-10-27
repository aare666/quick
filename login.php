<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Replace with your real admin credentials
    if ($username === 'admin' && $password === 'high1') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = 'Invalid credentials!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>BaBox Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
      margin: 0;
      background: #f4f6f9;
      font-family: Arial, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .login-container {
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      text-align: center;
      width: 100%;
      max-width: 400px;
    }
    h1 {
      color: #2c3e50;
      margin-bottom: 30px;
      font-size: 22px;
    }
    input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 16px;
    }
    input:focus {
      outline: 2px solid #3498db;
      border-color: #3498db;
    }
    .btn {
      width: 100%;
      padding: 12px;
      background: #3498db;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      color: white;
      font-size: 16px;
      margin-top: 10px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .btn:hover { background: #2980b9; }
    .error {
      margin-top: 15px;
      color: #e74c3c;
      font-weight: bold;
    }
    .logo {
      font-size: 40px;
      margin-bottom: 10px;
      color: #3498db;
    }
  </style>
</head>
<body>
  <form class="login-container" method="POST">
    <div class="logo">🔐</div>
    <h1>BaBox Admin Login</h1>
    <input type="text" name="username" placeholder="Username" required />
    <input type="password" name="password" placeholder="Password" required />
    <button class="btn" type="submit">Login</button>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </form>
</body>
</html>
