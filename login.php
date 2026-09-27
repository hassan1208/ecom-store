<?php
// login.php — public customer login (guest checkout still works without this)
require_once __DIR__ . '/includes/config.php';

if (is_customer()) { header('Location: ' . url('account')); exit; }

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? url('account');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic throttling against brute-force guessing (separate counter from admin login).
    $_SESSION['customer_login_attempts'] = ($_SESSION['customer_login_attempts'] ?? 0) + 1;
    $_SESSION['customer_login_attempts_at'] = $_SESSION['customer_login_attempts_at'] ?? time();
    if (time() - $_SESSION['customer_login_attempts_at'] > 300) {
        $_SESSION['customer_login_attempts'] = 1;
        $_SESSION['customer_login_attempts_at'] = time();
    }

    if ($_SESSION['customer_login_attempts'] > 8) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } elseif (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } else {
        $user = fetch_one("SELECT * FROM users WHERE email=? AND role='customer'", 's', $email);
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = 'customer';
            $_SESSION['user_name'] = trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''));
            unset($_SESSION['customer_login_attempts'], $_SESSION['customer_login_attempts_at']);
            update_record('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id', $user['id']);
            header('Location: ' . $redirect);
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

$meta_title = 'Sign In | ' . setting('site_name');
$meta_robots = 'noindex, follow';
include __DIR__ . '/includes/site-header.php';
require_once __DIR__ . '/includes/auth-shell.php';
auth_shell_open();
?>
  <h1 class="font-display font-bold uppercase text-3xl sm:text-4xl mb-2">Sign In</h1>
  <p class="text-slate-500 text-sm mb-8">New here? <a href="<?= url('register') . ($redirect !== url('account') ? '?redirect=' . urlencode($redirect) : '') ?>" class="text-ignite font-semibold">Create an account</a></p>

  <?php if ($error): ?>
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 text-red-700 px-4 py-2.5 text-sm font-medium"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
    <div>
      <label class="label">Email Address</label>
      <input type="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>" class="input">
    </div>
    <div>
      <label class="label">Password</label>
      <input type="password" name="password" required class="input">
    </div>
    <button type="submit" class="btn-primary btn-shine w-full">
      Sign In
    </button>
  </form>

  <p class="text-center text-slate-400 text-xs mt-8">You can still <a href="<?= url('cart') ?>" class="text-ignite font-semibold">checkout as a guest</a> without an account.</p>
<?php auth_shell_close(); ?>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
