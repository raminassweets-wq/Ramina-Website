<?php
require __DIR__.'/app/bootstrap.php';
if (current_user()) redirect('account.php');
$error=null;
if (post()) {
    check_csrf();
    $email=strtolower(value('email'));
    $password=is_string($_POST['password'] ?? null)?$_POST['password']:'';
    $key=hash('sha256','customer|'.($_SERVER['REMOTE_ADDR'] ?? '').'|'.$email);
    $attempt=one('SELECT attempts FROM login_attempts WHERE attempt_key=? AND last_attempt > DATE_SUB(NOW(), INTERVAL 15 MINUTE)',[$key]);
    if ($attempt && $attempt['attempts']>=5) $error='Too many attempts. Wait 15 minutes before trying again.';
    else {
        $candidate=one('SELECT * FROM users WHERE email=?',[$email]);
        $hash=$candidate['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (strlen($password)<=72 && password_verify($password,$hash) && $candidate && ($candidate['account_status'] ?? 'Active')==='Active') {
            if ($candidate['role']==='admin') {
                $error='That is an owner account. Use the separate Owner Login so customer and owner sessions can stay signed in together.';
            } else {
                query('DELETE FROM login_attempts WHERE attempt_key=?',[$key]);
                if (password_needs_rehash($hash,PASSWORD_DEFAULT)) query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$candidate['id']]);
                session_regenerate_id(true);
                $_SESSION['user_id']=(int)$candidate['id'];
                $_SESSION['session_version']=(int)($candidate['session_version'] ?? 1);
                $_SESSION['csrf']=bin2hex(random_bytes(32));
                unset($_SESSION['checkout_key']);
                flash('Welcome back, '.$candidate['name'].'.');
                redirect('account.php');
            }
        } else {
            query('INSERT INTO login_attempts (attempt_key,attempts,last_attempt) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,attempts+1),last_attempt=NOW()',[$key]);
            $error='Email or password is incorrect.';
        }
    }
}
page_header('Login');
?>
<div class="auth-layout premium-auth">
<div class="auth-intro">
<p class="eyebrow">WELCOME BACK</p>
<h1>Your sweet favourites are waiting.</h1>
<p>Sign in to track orders, manage your details, review completed purchases and speed up checkout.</p>
<div class="trust-list"><span>✓ Secure account</span><span>✓ Order history</span><span>✓ Email receipts</span></div>
<img src="images/sweets.svg" alt="Illustrated homemade sweets">
</div>
<section class="form-card auth-card">
<div class="auth-tabs"><a class="selected" href="login.php" aria-current="page">Customer Login</a><a href="register.php">Register</a></div>
<h2>Welcome back</h2>
<p class="muted center-text">Use the email address linked to your customer account.</p>
<?php error_box($error); ?>
<form method="post">
<?= csrf() ?>
<label for="email">Email address</label>
<input id="email" name="email" type="email" required maxlength="190" value="<?= e(value('email')) ?>" autocomplete="username" placeholder="you@example.com">
<label for="password">Password</label>
<input id="password" name="password" type="password" required maxlength="72" autocomplete="current-password" placeholder="Your password">
<button class="button wide">Sign in securely</button>
</form>
<div class="auth-help-row"><a class="text-link" href="forgot-password.php">Forgot password?</a><a class="text-link" href="admin/login.php">Owner login →</a></div>
<p class="auth-switch">New here? <a class="text-link" href="register.php">Create an account</a></p>
</section>
</div>
<?php page_footer(); ?>
