<?php
// register.php — public customer registration (optional; guest checkout still works)
require_once __DIR__ . '/includes/config.php';

if (is_customer()) { header('Location: ' . url('account')); exit; }

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? url('account');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name  = sanitize($_POST['last_name'] ?? '');
    $email      = sanitize($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if ($first_name === '') $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors && fetch_one("SELECT id FROM users WHERE email=?", 's', $email)) {
        $errors[] = 'An account with this email already exists. Please sign in instead.';
    }

    if (!$errors) {
        $user_id = insert('users', [
            'first_name' => $first_name,
            'last_name'  => $last_name ?: null,
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => 'customer',
            'status'     => 'active',
        ]);
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user_id;
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = trim($first_name . ' ' . $last_name);
        header('Location: ' . $redirect);
        exit;
    }
}

$meta_title = 'Create Account | ' . setting('site_name');
$meta_robots = 'noindex, follow';
include __DIR__ . '/includes/site-header.php';
require_once __DIR__ . '/includes/auth-shell.php';
auth_shell_open();
?>
  <h1 class="font-display font-bold uppercase text-3xl sm:text-4xl mb-2">Create Account</h1>
  <p class="text-slate-500 text-sm mb-8">Already have an account? <a href="<?= url('login') . ($redirect !== url('account') ? '?redirect=' . urlencode($redirect) : '') ?>" class="text-ignite font-semibold">Sign in</a></p>

  <?php if ($errors): ?>
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 text-red-700 p-4 text-sm">
    <ul class="list-disc pl-4 space-y-0.5"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="label">First Name *</label>
        <input type="text" name="first_name" required value="<?= h($_POST['first_name'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="label">Last Name</label>
        <input type="text" name="last_name" value="<?= h($_POST['last_name'] ?? '') ?>" class="input">
      </div>
    </div>
    <div>
      <label class="label">Email Address *</label>
      <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>" class="input">
    </div>
    <div>
      <label class="label">Password *</label>
      <input type="password" name="password" required minlength="6" class="input">
    </div>
    <div>
      <label class="label">Confirm Password *</label>
      <input type="password" name="confirm_password" required minlength="6" class="input">
    </div>
    <button type="submit" class="btn-primary btn-shine w-full">
      Create Account
    </button>
  </form>
<?php auth_shell_close(); ?>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
