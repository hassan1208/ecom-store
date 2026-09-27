<?php
// admin/login.php
require_once __DIR__ . '/../includes/config.php';

if (is_admin()) { header('Location: ' . ADMIN_URL . '/index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic throttling against brute-force guessing
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts_at'] = $_SESSION['login_attempts_at'] ?? time();
    if (time() - $_SESSION['login_attempts_at'] > 300) {
        $_SESSION['login_attempts'] = 1;
        $_SESSION['login_attempts_at'] = time();
    }

    if ($_SESSION['login_attempts'] > 8) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } elseif (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } else {
        $user = fetch_one("SELECT * FROM users WHERE email = ? AND role = 'admin'", 's', $email);
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''));
            unset($_SESSION['login_attempts'], $_SESSION['login_attempts_at']);
            update_record('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id', $user['id']);
            header('Location: ' . ADMIN_URL . '/index.php');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login · BuiltCo Sports</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { theme: { extend: {
    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
    colors: { ink: '#0d1321', brand: { DEFAULT: '#059669', dark: '#047857' } },
  } } };
</script>
</head>
<body class="font-sans bg-ink min-h-screen flex items-center justify-center p-4"
      style="background-image:radial-gradient(circle at 20% 20%, rgba(5,150,105,.25), transparent 40%), radial-gradient(circle at 80% 80%, rgba(5,150,105,.15), transparent 40%);">
  <div class="w-full max-w-sm">
    <div class="text-center mb-6">
      <div class="w-14 h-14 rounded-2xl bg-brand mx-auto flex items-center justify-center text-white text-2xl font-extrabold shadow-lg shadow-brand/30">B</div>
      <h1 class="text-white font-bold text-xl mt-4">BuiltCo Sports</h1>
      <p class="text-slate-400 text-sm">Admin Panel</p>
    </div>
    <div class="bg-white rounded-2xl shadow-2xl p-7">
      <h2 class="text-lg font-bold text-slate-800 mb-5">Sign in to continue</h2>
      <?php if ($error): ?>
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-2.5 text-sm font-medium">
        <?= h($error) ?>
      </div>
      <?php endif; ?>
      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
          <input type="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>"
                 class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand transition">
        </div>
        <div class="mb-5">
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
          <input type="password" name="password" required
                 class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand transition">
        </div>
        <button type="submit" class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-semibold py-2.5 text-sm transition shadow-sm">
          Sign In
        </button>
      </form>
    </div>
    <p class="text-center text-slate-500 text-xs mt-5">&copy; <?= date('Y') ?> BuiltCo Sports. All rights reserved.</p>
  </div>
</body>
</html>
